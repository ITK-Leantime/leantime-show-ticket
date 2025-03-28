async function copyCurrentUrl() {
  const url = document.location.href;
  const button = window["copy-url-button"];

  try {
    await navigator.clipboard.writeText(url);
    saveSuccess(button);
  } catch (error) {
    saveError(button);
  }
}

function saveTicket(input, key, defaultValueInput = null) {
  startSpinner();
  const { id } = document.querySelector("main");
  const defaultValue = defaultValueInput ?? input.defaultValue;
  const { value } = input;

  fetch("/ShowTicket/ShowTicket/saveTicket", {
    method: "POST",
    body: JSON.stringify({ key, value, id }),
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => response.json())
    .then(({ ticket: { original } }) => {
      input.value = original[key];
      input.defaultValue = original[key];
      saveSuccess(input);
    })
    .catch((error) => {
      console.error("Error: ", error);
      saveError(input);
      input.value = defaultValue;
      input.defaultValue = defaultValue;
    })
    .finally(() => stopSpinner());
}

function deleteTicket() {
  startSpinner()
  const { id } = document.querySelector("main");

  fetch("/ShowTicket/ShowTicket/deleteTicket", {
    method: "POST",
    body: JSON.stringify({ id }),
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => response.json())
    .then(() => {
      location.reload();
    })
    .catch((error) => {
      console.error("Ticket was not deleted:", error)
    }) .finally(() => stopSpinner());
}

function saveDateToFinish(input) {
  startSpinner();
  const { id } = document.querySelector("main");
  const defaultValue = input.defaultValue;
  const { value } = input;

  fetch("/ShowTicket/ShowTicket/saveTicket", {
    method: "POST",
    body: JSON.stringify({ key: "dateToFinish", value: formatDate(value), id }),
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => response.json())
    .then(({ ticket: { original } }) => {
      input.value = formatDateToDatetimeInput(original.dateToFinish);
      input.defaultValue = formatDateToDatetimeInput(original.dateToFinish);
      saveSuccess(input);
    })
    .catch((error) => {
      console.error("Error fetching projects:", error);
      input.value = defaultValue;
      input.defaultValue = defaultValue;
      saveError(input);
    })
    .finally(() => stopSpinner());
}

document.addEventListener("DOMContentLoaded", function () {
  const statusDefaultValue = document.getElementById("status-select").value;
  const priorityDefaultValue = document.getElementById("priority-select").value;
  const userDefaultValue = document.getElementById("user-select").value;
  // Right now this breaks any formatting done
  // leantime uses tox-editor-container, perhaps we should too?
  window["description-input"].value = restoreString(window["description-input"].value);

  window["headline-input"].addEventListener("change", function () {
    const input = window["headline-input"];
    saveTicket(input, "headline");
  });

  window["delete-ticket"].addEventListener("click", function () {
    deleteTicket();
  });

  window["sprint-select"].addEventListener("change", function () {
    const input = window["sprint-select"];
    saveTicket(input, "sprint");
  });

  window["milestone-select"].addEventListener("change", function () {
    const input = window["milestone-select"];
    saveTicket(input, "milestoneid");
  });

  window["tags-input"].addEventListener("change", function () {
    const input = window["tags-input"];
    saveTicket(input, "tags");
  });

  window["related-tickets-select"].addEventListener("change", function () {
    const input = window["related-tickets-select"];
    saveTicket(input, "dependingTicketId");
  });

  window["status-select"].addEventListener("change", function () {
    const input = window["status-select"];
    saveTicket(input, "status", statusDefaultValue);
  });

  window["priority-select"].addEventListener("change", function () {
    const input = window["priority-select"];
    saveTicket(input, "priority", priorityDefaultValue);
  });

  window["date-to-finish-input"].addEventListener("change", function () {
    const input = window["date-to-finish-input"];
    saveDateToFinish(input);
  });

  window["description-input"].addEventListener("change", function () {
    const input = window["description-input"];
    saveTicket(input, "description");
  });

  window["plan-hours-input"].addEventListener("change", function () {
    const input = window["plan-hours-input"];
    saveTicket(input, "planHours");
  });

  window["user-select"].addEventListener("change", function () {
    const input = window["user-select"];
    saveTicket(input, "editorId", userDefaultValue);
  });
});

function startSpinner() {
  window["spinner"].style.display = "flex";
}

function stopSpinner() {
  window["spinner"].style.display = "none";
}

function saveSuccess(elem) {
  elem.classList.add("save-success");

  setTimeout(() => {
    elem.classList.remove("save-success");
  }, 1000);
}

function saveError(elem) {
  elem.classList.add("save-error");

  setTimeout(() => {
    elem.classList.remove("save-error");
  }, 1000);
}

function restoreString(htmlString) {
  const div = document.createElement("div");
  div.innerHTML = htmlString;
  return div.textContent || div.innerText;
}

function formatDate(date) {
  const localDate = new Date(date);
  const yyyy = localDate.getFullYear();
  let mm = localDate.getMonth() + 1; // Months start at 0!
  let dd = localDate.getDate();

  dd = dd < 10 ? `0${dd}` : dd;
  mm = mm < 10 ? `0${mm}` : mm;

  return dd + "/" + mm + "/" + yyyy;
}

function formatDateToDatetimeInput(date) {
  const localDate = new Date(date);
  const yyyy = localDate.getFullYear();
  let mm = localDate.getMonth() + 1; // Months start at 0!
  let dd = localDate.getDate();

  dd = dd < 10 ? `0${dd}` : dd;
  mm = mm < 10 ? `0${mm}` : mm;

  return yyyy + "-" + mm + "-" + dd;
}
