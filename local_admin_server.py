import json
import os
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from urllib.parse import urlparse


ROOT_DIR = os.path.dirname(os.path.abspath(__file__))
CONTENT_DIR = os.path.join(ROOT_DIR, "content")
IMAGES_DIR = os.path.join(ROOT_DIR, "assets", "images")


class AdminHandler(SimpleHTTPRequestHandler):
    def _send_json(self, payload, status=200):
        data = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        self.send_response(status)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(data)))
        self.end_headers()
        self.wfile.write(data)

    def _safe_content_path(self, file_name: str):
        if not file_name.endswith(".json"):
            return None
        if "/" in file_name or "\\" in file_name:
            return None
        if not file_name.startswith("company-profile."):
            return None
        return os.path.join(CONTENT_DIR, file_name)

    def do_POST(self):
        parsed = urlparse(self.path)
        if parsed.path == "/api/save-json":
            return self._handle_save_json()
        if parsed.path == "/api/upload-images":
            return self._handle_upload_images()
        return self._send_json({"ok": False, "error": "Not found"}, status=404)

    def _handle_save_json(self):
        try:
            length = int(self.headers.get("Content-Length", "0"))
            raw = self.rfile.read(length)
            payload = json.loads(raw.decode("utf-8"))
        except Exception:
            return self._send_json({"ok": False, "error": "Invalid JSON body"}, status=400)

        file_name = payload.get("fileName", "")
        content = payload.get("content", "")
        target_path = self._safe_content_path(file_name)
        if not target_path:
            return self._send_json({"ok": False, "error": "Invalid target file"}, status=400)

        try:
            parsed_content = json.loads(content)
            normalized = json.dumps(parsed_content, ensure_ascii=False, indent=2) + "\n"
        except Exception:
            return self._send_json({"ok": False, "error": "content must be valid JSON text"}, status=400)

        os.makedirs(CONTENT_DIR, exist_ok=True)
        with open(target_path, "w", encoding="utf-8") as f:
            f.write(normalized)

        return self._send_json({"ok": True, "saved": file_name})

    def _handle_upload_images(self):
        try:
            length = int(self.headers.get("Content-Length", "0"))
            raw = self.rfile.read(length)
            payload = json.loads(raw.decode("utf-8"))
            files = payload.get("files", [])
        except Exception:
            return self._send_json({"ok": False, "error": "Invalid upload payload"}, status=400)

        if not isinstance(files, list) or not files:
            return self._send_json({"ok": False, "error": "files list is required"}, status=400)

        import base64
        os.makedirs(IMAGES_DIR, exist_ok=True)
        saved_paths = []
        for item in files:
            name = str(item.get("name", "")).strip()
            content_b64 = str(item.get("contentBase64", "")).strip()
            if not name or "/" in name or "\\" in name:
                return self._send_json({"ok": False, "error": f"Invalid file name: {name}"}, status=400)
            if not content_b64:
                return self._send_json({"ok": False, "error": f"Missing file content: {name}"}, status=400)
            try:
                binary = base64.b64decode(content_b64, validate=True)
            except Exception:
                return self._send_json({"ok": False, "error": f"Invalid base64 content: {name}"}, status=400)

            target_path = os.path.join(IMAGES_DIR, name)
            with open(target_path, "wb") as f:
                f.write(binary)
            saved_paths.append(f"assets/images/{name}")

        return self._send_json({"ok": True, "saved": saved_paths})


if __name__ == "__main__":
    os.chdir(ROOT_DIR)
    server = ThreadingHTTPServer(("127.0.0.1", 5500), AdminHandler)
    print("Admin server running at http://127.0.0.1:5500")
    server.serve_forever()
