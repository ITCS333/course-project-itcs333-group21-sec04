let currentAssignmentId = null;
let currentComments = [];

const assignmentTitle = document.querySelector("#assignment-title");
const assignmentDueDate = document.querySelector("#assignment-due-date");
const assignmentDescription = document.querySelector("#assignment-description");
const assignmentFilesList = document.querySelector("#assignment-files-list");
const commentList = document.querySelector("#comment-list");
const commentForm = document.querySelector("#comment-form");
const newCommentText = document.querySelector("#new-comment-text");

function getAssignmentIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderAssignmentDetails(assignment) {
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = "Due: " + assignment.dueDate;
  assignmentDescription.textContent = assignment.description;

  assignmentFilesList.innerHTML = "";
  const files = Array.isArray(assignment.files) ? assignment.files : [];
  files.forEach((file) => {
    const li = document.createElement("li");
    const a = document.createElement("a");
    a.href = "#";
    a.textContent = file;
    li.appendChild(a);
    assignmentFilesList.appendChild(li);
  });
}

function createCommentArticle(comment) {
  const article = document.createElement("article");

  const p = document.createElement("p");
  p.textContent = comment.text;

  const footer = document.createElement("footer");
  footer.textContent = "Posted by: " + comment.author;

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

  const text = newCommentText.value.trim();
  if (!text) return;

  const newComment = {
    author: "Student",
    text: text,
  };

  currentComments.push(newComment);
  renderComments();
  newCommentText.value = "";
}

async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();

  if (!currentAssignmentId) {
    assignmentTitle.textContent = "Error: No assignment ID.";
    return;
  }

  const assignmentsRes = await fetch("api/assignments.json");
  const commentsRes = await fetch("api/comments.json");

  const assignmentsData = await assignmentsRes.json();
  const commentsData = await commentsRes.json();

  const assignment = assignmentsData.find(
    (a) => String(a.id) === String(currentAssignmentId)
  );
  currentComments = commentsData[currentAssignmentId] || [];

  if (!assignment) {
    assignmentTitle.textContent = "Error: Assignment not found.";
    return;
  }

  renderAssignmentDetails(assignment);
  renderComments();

  commentForm.addEventListener("submit", handleAddComment);
}

initializePage();
