/// <reference path="./jquery-4.0.0.min.js" />

const gallery = await $.ajax({
  method: "GET",
  url: "/gallery.json",
});

$("#title").text(gallery.gallery);

for (const image of gallery.images) {
  const img = $(`<img src="${image.file}" alt="${image.name}" />`);
  $("#gallery").append(img);
  img.on("click", () => {
    img.toggleClass("img-fullscreen");
  });
}
