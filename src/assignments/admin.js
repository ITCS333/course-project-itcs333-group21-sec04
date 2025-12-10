document.addEventListener("DOMContentLoaded", () => {
  let assignments = [];
  const assignmentForm = document.querySelector("#assignment-form");
  const assignmentsTableBody = document.querySelector("#assignments-tbody");
  function createAssignmentRow(assignment) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.dueDate}</td>
    <td>
    <button class="edit-btn" data-id="${assignment.id}">Edit</button>
    <button class="delete-btn" data-id="${assignment.id}">Delete</button>
    </td>
    `;
    return tr;
  }

  function renderTable() {
    assignmentsTableBody.innerHTML = "";
    assignments.forEach((asg) => {
      const row = createAssignmentRow(asg);
      assignmentsTableBody.appendChild(row);
    });
  }

  function handleAddAssignment(event) {
    event.preventDefault();

    const title = document.querySelector("#assignment-title").value.trim();
    const description = document
      .querySelector("#assignment-description")
      .value.trim();
    const dueDate = document.querySelector("#assignment-due-date").value;
    const files = document.querySelector("#assignment-files").value.trim();

    const newAssignment = {
      id: `asg_${Date.now()}`,
      title,
      description,
      dueDate,
      files,
    };

    assignments.push(newAssignment);
    renderTable();
    assignmentForm.reset();
  }

  function handleTableClick(event) {
    if (event.target.classList.contains("delete-btn")) {
      const id = event.target.getAttribute("data-id");
      assignments = assignments.filter((a) => a.id !== id);
      renderTable();
    }
  }

  async function loadAndInitialize() {
    try {
      const res = await fetch("api/assignments.json");
      assignments = await res.json();
    } catch (e) {
      assignments = [];
    }

    renderTable();
    assignmentForm.addEventListener("submit", handleAddAssignment);
    assignmentsTableBody.addEventListener("click", handleTableClick);
  }

  loadAndInitialize();
});
