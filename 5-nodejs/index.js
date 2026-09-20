import fs from "node:fs";
import express from "express";
import mustache from "mustache";

const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static("public"));

const shared = {
  pageList: `
<aside>
  <h2>Список страниц:</h2>
  <nav>
    <ul>
      <li><a href="/">Домой</a></li>
      <li><a href="/about">О нас</a></li>
      <li><a href="/games">Список игр</a></li>
      <li><a href="/music">Музыка</a></li>
      <li><a href="/contact">Контакты</a></li>
    </ul>
  </nav>
</aside>`,
};

const readMustacheFile = (name) =>
  fs.readFileSync(`pages/${name}.mustache`, "utf-8");

const readDataFile = (name) =>
  JSON.parse(fs.readFileSync(`data/${name}.json`, "utf-8"));

const renderPage = (path) => {
  const template = readMustacheFile(path);
  const data = readDataFile(path);
  return mustache.render(template, { ...shared, ...data });
};

const handlePage = (path) => (_, res) => res.send(renderPage(path));

app.get("/", handlePage("index"));
app.get("/about", handlePage("about"));
app.get("/games", handlePage("games"));
app.get("/music", handlePage("music"));
app.get("/contact", handlePage("contact"));

app.post("/submit", (req, res) => {
  const template = readMustacheFile("result");

  const result = mustache.render(template, {
    ...shared,
    title: "Результат отправки формы",
    form: Object.entries(req.body).map(([key, value]) => ({ key, value })),
  });

  res.send(result);
});

const PORT = 3000;
app.listen(PORT, () => console.log(`Сервер запущен: http://127.0.0.1:${PORT}`));
