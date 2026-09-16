/// <reference path="./jquery-ui-1.14.2/external/jquery/jquery.js" />

const $galleryTabs = $("#gallery-tabs");
const $tabList = $("#tab-list");
const $progressbar = $("#progressbar");
const $message = $("#load-message");
const $viewer = $("#viewer");
let images = [];
let activeIndex = 0;

$("#accordion").accordion({ collapsible: true, heightStyle: "content" });
$("#gallery-date").datepicker({ dateFormat: "dd.mm.yy", showAnim: "drop" });
$progressbar.progressbar({ value: 0 });

function categoryFor(image) {
  return (
    image.category ||
    (image.name.includes("Cow")
      ? "Ферма"
      : image.name.includes("Bird")
        ? "Птицы"
        : "Домашние животные")
  );
}

function setActive(index, shouldScroll = true) {
  activeIndex = (index + images.length) % images.length;
  const image = images[activeIndex];
  $("#slide-caption").text(
    `${activeIndex + 1} / ${images.length}: ${image.name}`,
  );
  $("#gallery-slider").slider("value", activeIndex);
  $(".gallery-image")
    .removeClass("is-selected")
    .filter(`[data-index="${activeIndex}"]`)
    .addClass("is-selected");
  if (shouldScroll)
    document
      .querySelector(`.gallery-image[data-index="${activeIndex}"]`)
      ?.scrollIntoView({
        behavior: "smooth",
        inline: "center",
        block: "nearest",
      });
}

function showViewer(index) {
  setActive(index);
  const image = images[activeIndex];
  $("#viewer-image").attr({ src: image.file, alt: image.name });
  $("#viewer-caption").text(image.name);
  $viewer.removeAttr("hidden").hide().show("drop", { direction: "up" }, 320);
  $("#next-image").trigger("focus");
}

function closeViewer() {
  $viewer.hide("drop", { direction: "down" }, 220, () =>
    $viewer.attr("hidden", "hidden"),
  );
}

function renderGallery(data) {
  images = data.images.map((image) => ({
    ...image,
    category: categoryFor(image),
  }));
  $("#title").text(data.gallery);
  const categories = ["Все", ...new Set(images.map((image) => image.category))];
  categories.forEach((category, tabIndex) => {
    const panelId = `tab-${tabIndex}`;
    $tabList.append(
      $("<li>").append($("<a>", { href: `#${panelId}`, text: category })),
    );
    const $panel = $("<div>", { id: panelId, class: "gallery-panel" });
    images
      .filter((image) => category === "Все" || image.category === category)
      .forEach((image) => {
        const originalIndex = images.indexOf(image);
        const $button = $("<button>", {
          type: "button",
          class: "gallery-image",
          "data-index": originalIndex,
          "aria-label": `Открыть: ${image.name}`,
        });
        const $img = $("<img>", {
          src: image.file,
          alt: image.name,
          loading: "lazy",
        });
        $button.append($img, $("<span>", { text: image.name }));
        $button.on("click", () => showViewer(originalIndex));
        $panel.append($button);
      });
    $galleryTabs.append($panel);
  });
  $galleryTabs.tabs({ activate: () => setActive(activeIndex, false) });
  $("#gallery-slider").slider({
    min: 0,
    max: images.length - 1,
    value: 0,
    slide: (_, ui) => setActive(ui.value),
    change: (_, ui) => {
      if (ui.value !== activeIndex) setActive(ui.value);
    },
  });
  setActive(0, false);
}

function watchImageLoading() {
  // Считаем исходные пять изображений на вкладке «Все», а не их копии в категориях.
  const $thumbnails = $("#tab-0 .gallery-image img");
  let completed = 0;
  const update = () => {
    completed += 1;
    const percent = Math.round((completed / $thumbnails.length) * 100);
    $progressbar.progressbar("value", percent);
    $message.text(
      percent === 100
        ? `Готово: загружено ${completed} изображений`
        : `Загружено ${completed} из ${$thumbnails.length} изображений`,
    );
  };
  $thumbnails.each((_, image) =>
    $(image)
      .one("load error", update)
      .each(function () {
        if (this.complete) $(this).trigger("load");
      }),
  );
}

$("#toggle-style").on("click", () =>
  $(".gallery-image").toggleClass("gallery-image--rounded"),
);
$("#next-image").on("click", () => showViewer(activeIndex + 1));
$("#previous-image").on("click", () => showViewer(activeIndex - 1));
$("#close-viewer").on("click", closeViewer);
$(document).on("keydown", (event) => {
  if ($viewer.is(":visible")) {
    if (event.key === "Escape") closeViewer();
    if (event.key === "ArrowRight") showViewer(activeIndex + 1);
    if (event.key === "ArrowLeft") showViewer(activeIndex - 1);
  }
});

try {
  const data = await $.ajax({
    method: "GET",
    url: "gallery.json",
    dataType: "json",
  });
  renderGallery(data);
  watchImageLoading();
} catch (error) {
  console.error(error);
  $("#title").text("Не удалось загрузить галерею");
  $message.text("Проверьте, что проект открыт через локальный веб-сервер.");
}
