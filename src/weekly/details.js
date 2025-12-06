let currentWeekId = null;
let currentComments = [];
const weekTitle = document.getElementById('week-title');
const weekStartDate = document.getElementById('week-start-date');
const weekDescription = document.getElementById('week-description');
const weekLinksList = document.getElementById('week-links-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentText = document.getElementById('new-comment-text');
function getWeekIdFromURL() {
  const queryString = window.location.search; // e.g. "?id=1"
  const params = new URLSearchParams(queryString);
  const id = params.get('id');
  return id;
}
function renderWeekDetails(week) {
  if (!week) return;
  weekTitle.textContent = week.title;
  weekStartDate.textContent = 'Starts on: ' + week.startDate;
  weekDescription.textContent = week.description;
  weekLinksList.innerHTML = '';
  if (Array.isArray(week.links)) {
    week.links.forEach((linkUrl) => {
      const li = document.createElement('li');
      const a = document.createElement('a');
      a.href = linkUrl;
      a.textContent = linkUrl;
      a.target = '_blank'; 
      li.appendChild(a);
      weekLinksList.appendChild(li);
    });
  }
}
function createCommentArticle(comment) {
  const article = document.createElement('article');
  article.classList.add('comment');
  const p = document.createElement('p');
  p.textContent = comment.text;
  const footer = document.createElement('footer');
  footer.textContent = 'Posted by: ' + comment.author;
  article.appendChild(p);
  article.appendChild(footer);
  return article;
}
function renderComments() {
  commentList.innerHTML = '';
  currentComments.forEach((comment) => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}
function handleAddComment(event) {
  event.preventDefault();
  const commentText = newCommentText.value.trim();
  if (!commentText) {
    return;
  }
  const newComment = {
    author: 'Student',
    text: commentText,
  };
  currentComments.push(newComment);
  renderComments();
  newCommentText.value = '';
}
async function initializePage() {
  currentWeekId = getWeekIdFromURL();
  if (!currentWeekId) {
    if (weekTitle) {
      weekTitle.textContent = 'Week not found.';
    }
    return;
  }
  try {
    const [weeksResponse, commentsResponse] = await Promise.all([
      fetch('weeks.json'),
      fetch('week-comments.json'),
    ]);
    if (!weeksResponse.ok || !commentsResponse.ok) {
      throw new Error('Network response was not ok');
    }
    const [weeksData, commentsData] = await Promise.all([
      weeksResponse.json(),
      commentsResponse.json(),
    ]);
    const week = Array.isArray(weeksData)
      ? weeksData.find((w) => String(w.id) === String(currentWeekId))
      : null;
    currentComments =
      (commentsData && Array.isArray(commentsData[currentWeekId]))
        ? commentsData[currentWeekId]
        : [];
    if (week) {
      renderWeekDetails(week);
      renderComments();
      if (commentForm) {
        commentForm.addEventListener('submit', handleAddComment);
      }
    } else {
      weekTitle.textContent = 'Week not found.';
    }
  } catch (error) {
    console.error('Error initializing page:', error);
    if (weekTitle) {
      weekTitle.textContent = 'Error loading week data.';
    }
  }
}
initializePage();
