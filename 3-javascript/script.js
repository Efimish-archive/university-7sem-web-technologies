const arr = [
  { name: "apple", count: 5, price: 70 },
  { name: "orange", count: 10, price: 90 },
];

let sum = 0;
arr.forEach((el) => (sum += el.count * el.price));
// const sum = arr.reduce((acc, el) => acc + el.price * el.count, 0);
console.log(sum);

const obj = {
  bill: arr,
  result: sum,
};

console.log(JSON.stringify(obj, null, 2));
console.log(
  new Date().toLocaleString("ru", {
    timeZone: "Asia/Krasnoyarsk",
  }),
);

// part 2

document.addEventListener("DOMContentLoaded", () => {
  const ul = document.getElementById("list");
  const p = document.getElementById("copy-here");

  const copyButton = document.getElementById("copy-button");
  const editButton = document.getElementById("edit-button");
  const searchBar = document.getElementById("query");
  const searchButton = document.getElementById("search-button");

  copyButton.addEventListener("click", (e) => {
    e.preventDefault();
    const lis = ul.querySelectorAll("li");
    lis.forEach((li) => (p.textContent += li.textContent));
  });

  editButton.addEventListener("click", (e) => {
    e.preventDefault();
    document.querySelectorAll(".change").forEach((el) => {
      el.style.fontWeight = Math.floor(Math.random() * 10) * 100;
    });
  });

  searchButton.addEventListener("click", (e) => {
    e.preventDefault();
    if (!searchBar.value) return;
    const lis = ul.querySelectorAll("li");
    lis.forEach((li) => {
      if (
        li.textContent.toLowerCase().includes(searchBar.value.toLowerCase())
      ) {
        li.style.backgroundColor = "#ffff0077";
      } else {
        li.style.backgroundColor = "";
      }
    });
  });
});
