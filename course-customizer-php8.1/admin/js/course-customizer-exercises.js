// Ensure DOM is fully loaded before starting
function ensureDOMReady() {
  return new Promise((resolve) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", resolve);
    } else {
      resolve();
    }
  });
}

const formatResult = (result, isTime) => {
  if (!isTime) {
    return escapeHtml(result);
  }

  // Convert seconds to HH:MM:SS format
  const hours = Math.floor(result / 3600);
  const minutes = Math.floor((result % 3600) / 60);
  const seconds = result % 60;

  return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
};

function attach_edit_button_listeners() {
  const cells = document.querySelectorAll(".trigger");

  cells.forEach((cell) => {
    const editIcon = cell.querySelector(".hover-target");
    const initialValue = cell.childNodes[0].textContent.trim();

    cell.addEventListener("click", function (e) {
      if (e.target.classList.contains("dashicons-edit")) {
        startEditing(cell, initialValue);
      }
    });
  });
}

function startEditing(cell, initialValue) {
  const currentValue = cell.childNodes[0].textContent.trim();

  // Create edit container
  const editContainer = document.createElement("div");
  editContainer.className = "edit-container inline-cell";

  // Create input
  const input = document.createElement("input");
  input.type = "number";
  input.className = "edit-input";
  input.value = currentValue;

  // Create save icon
  const saveIcon = document.createElement("span");
  saveIcon.className = "dashicons dashicons-saved save-button";

  // Clear cell and add new elements
  cell.innerHTML = "";
  editContainer.appendChild(input);
  editContainer.appendChild(saveIcon);
  cell.appendChild(editContainer);

  input.focus();

  // Handle save
  saveIcon.addEventListener(
    "click",
    save.bind(null, input, cell, initialValue),
  );
  input.addEventListener("keyup", function (e) {
    if (e.key === "Enter") save(input, cell, initialValue);
    if (e.key === "Escape") restoreCell(initialValue, cell);
  });
}

function save(input, cell, initialValue) {
  const newValue = input.value;
  if (newValue === initialValue) {
    console.log("Detected no change, not saving");
    return;
  }
  const exerciseId = cell
    .closest("tr")
    .querySelector('input[name="exercise_id"]').value;

  const formData = new FormData();
  formData.append("action", "update_exercise_min");
  formData.append("exercise_id", exerciseId);
  formData.append("min_value", newValue);
  formData.append("nonce", exerciseAdminObj.nonce);

  fetch(exerciseAdminObj.ajaxurl, {
    method: "POST",
    body: formData,
    credentials: "same-origin",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        restoreCell(data.data.new_value, cell);
      } else {
        alert(exerciseAdminObj.strings.saveFailed);
        restoreCell(initialValue, cell);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert(exerciseAdminObj.strings.saveFailed);
      restoreCell(initialValue, cell);
    });
}

function restoreCell(value, cell) {
  cell.innerHTML =
    value + '<span class="hover-target dashicons dashicons-edit"></span>';
}

// Main initialization function
async function init() {
  try {
    attach_edit_button_listeners();

    console.log("Quiz initialization completed successfully");
  } catch (error) {
    console.error("Failed to initialize quiz:", error);
    throw error;
  }
}

// Start the application with proper error handling
async function startApplication() {
  try {
    // Ensure DOM is ready before starting
    await ensureDOMReady();

    // Initialize the application
    await init();

    console.log("Application started successfully");
  } catch (error) {
    console.error("Failed to start application:", error);
  }
}

// Start the application when the script loads
startApplication();
