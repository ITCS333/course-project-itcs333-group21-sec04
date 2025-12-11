document.addEventListener("DOMContentLoaded", () => {
  console.log("admin.js loading");
  let assignments = [];
  const assignmentForm = document.querySelector("#assignment-form");
  const assignmentsTableBody = document.querySelector("#assignments-tbody");
  console.log("tbody =", assignmentsTableBody);
  function createAssignmentRow(assignment) {
    const tr = document.createElement("tr");
    tr.innerHTML = `
    <td>${assignment.title}</td>
    <td>${assignment.due_date}</td>
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

  async function handleAddAssignment(event) {
    event.preventDefault();

    const title = document.querySelector("#assignment-title").value.trim();
    const description = document
      .querySelector("#assignment-description")
      .value.trim();
    const due_date = document.querySelector("#assignment-due-date").value;
    const files = document.querySelector("#assignment-files").value.trim();

    const newAssignment = {
      id: `asg_${Date.now()}`,
      title,
      description,
      due_date,
      files,
    };

    const res = await fetch("api/index.php?resource=assignments", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        title,
        description,
        due_date: due_date,
        files: files ? files.split(",").map((f) => f.trim()) : [],
      }),
    });

    const created = await res.json();
    assignments.push(created);
    renderTable();
    assignmentForm.reset();
  }

  async function handleTableClick(event) {
    if (event.target.classList.contains("delete-btn")) {
      const id = event.target.getAttribute("data-id");
      await fetch(`api/index.php?resource=assignments&id=${id}`, {
        method: "DELETE",
      });
      await fetch(`api/index.php?resource=assignments&id=${id}`, {
        method: "DELETE",
      });
      assignments = assignments.filter((a) => String(a.id) !== String(id));
      renderTable();
    }
  }

  async function loadAndInitialize() {
    try {
      const res = await fetch("api/index.php?resource=assignments");
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
