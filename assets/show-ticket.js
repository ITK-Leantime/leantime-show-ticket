import "tinymce/tinymce";
import "tinymce/icons/default";
import "tinymce/themes/silver";
import "tinymce/models/dom";

import "tinymce/plugins/link";
import "tinymce/plugins/table";
import "tinymce/plugins/code";
import "tinymce/skins/ui/oxide/skin.css";
import "tinymce/skins/content/default/content.css";
import "tinymce/plugins/advlist";
import "tinymce/plugins/lists";
import "tinymce/plugins/code";
import DOMPurify from "dompurify";

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

async function simpleSaveTicketWrapper(input, key, defaultValueInput = null) {
  const defaultValue = defaultValueInput ?? input.defaultValue;
  const { value } = input;
  const { original: ticket = {}, error } = await saveTicket(
    value,
    key,
    defaultValue,
  );

  if (error) {
    input.value = defaultValue;
    input.defaultValue = defaultValue;
    saveError(input);
  } else if (ticket) {
    input.value = ticket[key];
    input.defaultValue = ticket[key];
    saveSuccess(input);
  }
}

async function saveTicket(value, key) {
  startSpinner();
  const { id } = document.querySelector("main");
  try {
    const response = await fetch("/ShowTicket/ShowTicket/saveTicket", {
      method: "POST",
      body: JSON.stringify({ key, value, id }),
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(response.statusText);
    }
    const { ticket } = await response.json();

    stopSpinner();
    return ticket;
  } catch (error) {
    stopSpinner();
    return { error: true, errorText: error };
  }
}

function deleteTicket() {
  startSpinner();
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
      console.error("Ticket was not deleted:", error);
    })
    .finally(() => stopSpinner());
}

async function saveDateToFinish(input) {
  const defaultValue = input.defaultValue;
  const { value } = input;

  const { original: ticket = {}, error } = await saveTicket(
    formatDate(value),
    "dateToFinish",
  );

  if (error) {
    input.value = defaultValue;
    input.defaultValue = defaultValue;
    saveError(input);
  } else if (ticket) {
    input.value = formatDateToDatetimeInput(ticket.dateToFinish);
    input.defaultValue = formatDateToDatetimeInput(ticket.dateToFinish);
    saveSuccess(input);
  }
}

document.addEventListener("DOMContentLoaded", function () {
  // Some default values, if there is a save error.
  const descriptionDefaultValue = tinymce?.activeEditor?.getContent();
  const statusDefaultValue = document.getElementById("status-select").value;
  const priorityDefaultValue = document.getElementById("priority-select").value;
  const userDefaultValue = document.getElementById("user-select").value;

  // tinyMCE for rich text description edit
  tinymce.init({
    selector: "#description-input",
    plugins: "link table code",
    toolbar:
      "undo redo | formatselect | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | code",
    height: 300,
    branding: false,
    skin: false,
    content_css: false,
    license_key: "gpl",
    setup: function (editor) {
      editor.on("change", async function () {
        const content = tinymce.activeEditor.getContent();
        const sanitizedContent = DOMPurify.sanitize(content);

        const { original: ticket = {}, error } = await saveTicket(
          sanitizedContent,
          "description",
        );
        if (error) {
          tinymce.activeEditor.setContent(descriptionDefaultValue);
          saveError(window["rich-text-success"]);
        } else if (ticket) {
          tinymce.activeEditor.setContent(ticket.description);
          saveSuccess(window["rich-text-success"]);
        }
      });
    },
  });

  // Event listeners
  // Buttons in top bar
  window["copy-url-button"].addEventListener("click", function () {
    copyCurrentUrl();
  });

  window["delete-ticket"].addEventListener("click", function () {
    deleteTicket();
  });

  // The following are much alike, and is change in the different inputs
  window["headline-input"].addEventListener("change", function () {
    const input = window["headline-input"];
    simpleSaveTicketWrapper(input, "headline");
  });

  window["sprint-select"].addEventListener("change", function () {
    const input = window["sprint-select"];
    simpleSaveTicketWrapper(input, "sprint");
  });

  window["milestone-select"].addEventListener("change", function () {
    const input = window["milestone-select"];
    simpleSaveTicketWrapper(input, "milestoneid");
  });

  window["tags-input"].addEventListener("change", function () {
    const input = window["tags-input"];
    simpleSaveTicketWrapper(input, "tags");
  });

  window["related-tickets-select"].addEventListener("change", function () {
    const input = window["related-tickets-select"];
    simpleSaveTicketWrapper(input, "dependingTicketId");
  });

  window["status-select"].addEventListener("change", function () {
    const input = window["status-select"];
    simpleSaveTicketWrapper(input, "status", statusDefaultValue);
  });

  window["priority-select"].addEventListener("change", function () {
    const input = window["priority-select"];
    simpleSaveTicketWrapper(input, "priority", priorityDefaultValue);
  });

  window["date-to-finish-input"].addEventListener("change", function () {
    const input = window["date-to-finish-input"];
    saveDateToFinish(input);
  });

  window["plan-hours-input"].addEventListener("change", function () {
    const input = window["plan-hours-input"];
    simpleSaveTicketWrapper(input, "planHours");
  });

  window["user-select"].addEventListener("change", function () {
    const input = window["user-select"];
    simpleSaveTicketWrapper(input, "editorId", userDefaultValue);
  });
});

// Spinner animation in top bar
function startSpinner() {
  window["spinner"].style.display = "flex";
}

function stopSpinner() {
  window["spinner"].style.display = "none";
}

// Save animations from project overview
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

// Stupid leantime dates confusing me, maybe these formatting functions are correct, I sure hope so.
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
