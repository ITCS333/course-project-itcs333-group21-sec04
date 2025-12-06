let currentResourceId = null;
let currentComments = [];

const resourceTitle = document.querySelector("#resource-title");
const resourceDescription = document.querySelector("#resource-description");
const resourceLink = document.querySelector("#resource-link");
const commentList = document.querySelector("#comment-list");
const commentForm = document.querySelector("#comment-form");
const newComment = document.querySelector("#new-comment");

function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.href = resource.link;
}

function createCommentArticle(comment) {
  const article = document.createElement("article");
  article.classList.add("comment");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = `Posted by: ${comment.author}`;

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}
function renderComments() {
  commentList.innerHTML = ""; 

  currentComments.forEach((comment) => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}
function handleAddComment(event) {
  event.preventDefault();

  const commentText = newComment.value.trim();
  if (!commentText) return;

  const newEntry = {
    author: "Student",
    text: commentText
  };

  currentComments.push(newEntry);

  renderComments();

  newComment.value = "";
}
async function initializePage() {
  currentResourceId = getResourceIdFromURL();

  if (!currentResourceId) {
    resourceTitle.textContent = "Resource not found.";
    return;
  }

  try {
    const [resourcesResponse, commentsResponse] = await Promise.all([
      fetch("resources.json"),
      fetch("resource-comments.json")
    ]);

    const resourcesData = await resourcesResponse.json();
    const commentsData = await commentsResponse.json();

    const resource = resourcesData.find(r => r.id === currentResourceId);
    currentComments = commentsData[currentResourceId] || [];

    if (!resource) {
      resourceTitle.textContent = "Resource not found.";
      return;
    }

    renderResourceDetails(resource);
    renderComments();

    commentForm.addEventListener("submit", handleAddComment);

  } catch (error) {
    console.error("Error loading resource:", error);
    resourceTitle.textContent = "Error loading resource.";
  }
}
initializePage();
