document.addEventListener("DOMContentLoaded", () => {
  const listSection = document.querySelector("#assignment-list-section");

  function createAssignmentArticle(assignment) {
    const article = document.createElement("article");
    article.classList.add("assignment-item");

    article.innerHTML = `
    <h2>${assignment.title}</h2>
    <p>Due: ${assignment.due_date}</p>
    <p>${assignment.description}</p>
    <a href="details.html?id=${assignment.id}" class="btn">View Details</a>
  `;

    return article;
  }

  async function loadAssignments() {
    const res = await fetch("api/index.php?resource=assignments");
    const assignments = await res.json();

    listSection.innerHTML = "";

    assignments.forEach((a) => {
      const article = createAssignmentArticle(a);
      listSection.appendChild(article);
    });
  }

  loadAssignments();
});
