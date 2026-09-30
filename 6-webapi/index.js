process.loadEnvFile();

import { Dropbox } from "dropbox";

const accessToken = process.env.DROPBOX_ACCESS_TOKEN;
if (!accessToken) {
  console.error("DROPBOX_ACCESS_TOKEN не найден в .env");
  process.exit(1);
}

const dbx = new Dropbox({ accessToken });

const listFiles = async () => {
  try {
    const request = await dbx.filesListFolder({ path: "" });
    const files = request.result.entries
      .filter((entry) => entry[".tag"] === "file")
      .map((entry) => ({
        name: entry.name,
        size: entry.size,
      }));

    console.log("Список файлов:", files);
  } catch (err) {
    console.error("Ошибка при выводе списка файлов:", err);
  }
};

const uploadFile = async () => {
  try {
    const request = await dbx.filesUpload({
      path: "/hello.txt",
      contents: "Hello, World!",
      mode: { ".tag": "overwrite" },
    });
    const result = request.result;

    console.log("Создан файл:", {
      name: result.name,
      size: result.size,
    });
  } catch (err) {
    console.error("Ошибка при создании файла:", err);
  }
};

await listFiles();
await uploadFile();
