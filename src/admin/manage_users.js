
let students = [];


const studentTableBody = document.querySelector("#student-table tbody");
const addStudentForm = document.querySelector("#add-student-form");
const changePasswordForm = document.querySelector("#password-form");
const searchInput = document.querySelector("#search-input");
const tableHeaders = document.querySelectorAll("#student-table thead th");



function createStudentRow(student) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${student.name}</td>
    <td>${student.id}</td>
    <td>${student.email}</td>
    <td>
      <button class="edit-btn btn btn-sm btn-warning" data-id="${student.id}">
        Edit
      </button>
      <button class="delete-btn btn btn-sm btn-danger" data-id="${student.id}">
        Delete
      </button>
    </td>
  `;

  return tr;
}

function renderTable(studentArray) {
  studentTableBody.innerHTML = "";

  studentArray.forEach(student => {
    const row = createStudentRow(student);
    studentTableBody.appendChild(row);
  });
}

function handleChangePassword(event) {
  event.preventDefault();

  const current = document.getElementById("current-password").value;
  const newPass = document.getElementById("new-password").value;
  const confirm = document.getElementById("confirm-password").value;

  if (newPass !== confirm) {
    alert("Passwords do not match.");
    return;
  }

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  alert("Password updated successfully!");

  document.getElementById("current-password").value = "";
  document.getElementById("new-password").value = "";
  document.getElementById("confirm-password").value = "";
}

function handleAddStudent(event) {
  event.preventDefault();

  const name = document.getElementById("student-name").value.trim();
  const id = document.getElementById("student-id").value.trim();
  const email = document.getElementById("student-email").value.trim();

  if (!name || !id || !email) {
    alert("Please fill out all required fields.");
    return;
  }

  const exists = students.some(student => student.id === id);
  if (exists) {
    alert("Student with this ID already exists.");
    return;
  }

  students.push({ name, id, email });
  renderTable(students);

  document.getElementById("student-name").value = "";
  document.getElementById("student-id").value = "";
  document.getElementById("student-email").value = "";
  document.getElementById("default-password").value = "";
}

function handleTableClick(event) {
  if (event.target.classList.contains("delete-btn")) {
    const id = event.target.dataset.id;
    students = students.filter(student => student.id !== id);
    renderTable(students);
  }

  if (event.target.classList.contains("edit-btn")) {
    alert("Edit functionality can be implemented here.");
  }
}

function handleSearch() {
  const term = searchInput.value.toLowerCase();

  if (!term) {
    renderTable(students);
    return;
  }

  const filtered = students.filter(student =>
    student.name.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

function handleSort(event) {
  const index = event.currentTarget.cellIndex;
  let key;

  if (index === 0) key = "name";
  else if (index === 1) key = "id";
  else if (index === 2) key = "email";
  else return;

  const currentDir = event.currentTarget.dataset.sortDir || "asc";
  const newDir = currentDir === "asc" ? "desc" : "asc";
  event.currentTarget.dataset.sortDir = newDir;

  students.sort((a, b) => {
    let result;
    if (key === "id") {
      result = Number(a[key]) - Number(b[key]);
    } else {
      result = a[key].localeCompare(b[key]);
    }
    return newDir === "asc" ? result : -result;
  });

  renderTable(students);
}

async function loadStudentsAndInitialize() {
  try {
    const response = await fetch("students.json");
    if (!response.ok) {
      console.error("Failed to load students.json");
      return;
    }

    students = await response.json();
    renderTable(students);

    changePasswordForm.addEventListener("submit", handleChangePassword);
    addStudentForm.addEventListener("submit", handleAddStudent);
    studentTableBody.addEventListener("click", handleTableClick);
    searchInput.addEventListener("input", handleSearch);
    tableHeaders.forEach(th =>
      th.addEventListener("click", handleSort)
    );

  } catch (error) {
    console.error("Error loading students:", error);
  }
}

loadStudentsAndInitialize();
