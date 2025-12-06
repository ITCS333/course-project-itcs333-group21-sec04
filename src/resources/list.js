const listSection = document.querySelector("#resource-list-section");

function createResourceArticle(resource) {
  const { id, title, description } = resource;

  const article = document.createElement("article");
  article.classList.add("resource");

  const heading = document.createElement("h2");
  heading.textContent = title;

  const desc = document.createElement("p");
  desc.textContent = description;

  const link = document.createElement("a");
  link.href = `details.html?id=${id}`;
  link.textContent = "View Resource & Discussion";

  article.appendChild(heading);
  article.appendChild(desc);
  article.appendChild(link);

  return article;
}
async function loadResources() {
  try {
    const response = await fetch("resources.json");
    const resources = await response.json();

    listSection.innerHTML = ""; // Clear any previous content

    resources.forEach(resource => {
      const article = createResourceArticle(resource);
      listSection.appendChild(article);
    });

  } catch (error) {
    console.error("Error loading resources:", error);
    listSection.innerHTML = "<p>Error loading resources.</p>";
  }
}
loadResources();
