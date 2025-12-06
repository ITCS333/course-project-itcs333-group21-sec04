let resources = [];
const resourceForm = document.querySelector("#resource-form");
const resourcesTableBody = document.querySelector("#resources-tbody");
function createResourceRow(resource) {
  const { id, title, description } = resource;

  const tr = document.createElement("tr");

  const titleTd = document.createElement("td");
  titleTd.textContent = title;

  const descTd = document.createElement("td");
  descTd.textContent = description;
  
  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = id;

  const deleteBtn = document.createElement("button");
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(titleTd);
  tr.appendChild(descTd);
  tr.appendChild(actionsTd);

  return tr;
}
function renderTable() {
  resourcesTableBody.innerHTML = ""; 

  resources.forEach((resource) => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });
}
function handleAddResource(event) {
  event.preventDefault();

  const titleInput = document.querySelector("#resource-title");
  const descInput = document.querySelector("#resource-description");
  const linkInput = document.querySelector("#resource-link");

  const newResource = {
    id: `res_${Date.now()}`,
    title: titleInput.value.trim(),
    description: descInput.value.trim(),
    link: linkInput.value.trim(),
  };

  resources.push(newResource);

  renderTable();
  resourceForm.reset();
}
function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    resources = resources.filter((res) => res.id !== id);

    renderTable();
  }
}
async function loadAndInitialize() {
  try {
    const response = await fetch("resources.json");
    resources = await response.json();

    renderTable();

    resourceForm.addEventListener("submit", handleAddResource);
    resourcesTableBody.addEventListener("click", handleTableClick);
  } catch (error) {
    console.error("Error loading resources.json:", error);
  }
}
loadAndInitialize();
