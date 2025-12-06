const listSection = document.getElementById('week-list-section');
function createWeekArticle(week) {
  const article = document.createElement('article');
  const h2 = document.createElement('h2');
  h2.textContent = week.title;
  const startP = document.createElement('p');
  startP.textContent = 'Starts on: ' + week.startDate;
  const descP = document.createElement('p');
  descP.textContent = week.description;
  const link = document.createElement('a');
  link.href = `details.html?id=${week.id}`;
  link.textContent = 'View Details & Discussion';
  article.appendChild(h2);
  article.appendChild(startP);
  article.appendChild(descP);
  article.appendChild(link);
  return article;
}
async function loadWeeks() {
  if (!listSection) return;
  try {
    const response = await fetch('weeks.json');
    if (!response.ok) {
      throw new Error('Failed to load weeks.json');
    }
    const weeks = await response.json(); // expected: array of week objects
    listSection.innerHTML = '';
    weeks.forEach((week) => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });
  } catch (error) {
    console.error('Error loading weeks:', error);
    listSection.innerHTML = '<p>Unable to load weeks at the moment.</p>';
  }
}
loadWeeks();
