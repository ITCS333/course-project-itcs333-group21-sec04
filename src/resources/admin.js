/*
  Requirement: Make the "Manage Resources" page interactive.
*/

// --- Global Data Store ---
let resources = [];

// --- Element Selections ---
const resourceForm = document.querySelector("#resource-form");
const resourcesTableBody = document.querySelector("#resources-tbody");

// --- Functions ---

/**
 * Creates a table row for a resource.
 * @param {Object} resource - { id, title, description, link }
 * @returns {HTMLTableRowElement}
 */
function createResourceRow(resource) {
  const { id, title, description } = resource;

  const tr = document.createElement("tr");

  // Title
  const titleTd = document.createElement("td");
  titleTd.textContent = title;

  // Description
  const descTd = document.createElement("td");
  descTd.textContent = description;

  // Actions
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

/**
 * Renders all resources to the table
 */
function renderTable() {
  resourcesTableBody.innerHTML = ""; // Clear table

  resources.forEach((resource) => {
    const row = createResourceRow(resource);
    resourcesTableBody.appendChild(row);
  });
}

/**
 * Handles form submission: Adds a new resource
 */
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

/**
 * Handles delete button clicks using event delegation
 */
function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    resources = resources.filter((res) => res.id !== id);

    renderTable();
  }
}

/**
 * Loads JSON + initializes page
 */
async function loadAndInitialize() {
  try {
    const response = await fetch("resources.json");
    resources = await response.json();

    renderTable();

    // Event listeners
    resourceForm.addEventListener("submit", handleAddResource);
    resourcesTableBody.addEventListener("click", handleTableClick);
  } catch (error) {
    console.error("Error loading resources.json:", error);
  }
}

// --- Initial Page Load ---
loadAndInitialize();
