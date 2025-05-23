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
import "tom-select/dist/css/tom-select.css";
import DOMPurify from "dompurify";
import TomSelect from "tom-select";
import {
  formatDateToDatetimeInput,
  formatDate,
  copyCurrentUrl,
  saveSuccess,
  saveError,
  initateTags,
} from "./helpers";

async function simpleSaveTicketWrapper(
  input,
  key,
  defaultValueInput = null,
  inputId = null,
) {
  const id = inputId ?? document.querySelector("main").id;
  const defaultValue = defaultValueInput ?? input.defaultValue;
  const { value } = input;

  const { original: ticket = {}, error } = await saveTicket(value, key, id);

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

async function saveTicket(value, key, inputId) {
  startSpinner();
  const id = inputId ?? document.querySelector("main").id;

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

function getTagsFromDom() {
  return window["selected-tags"]?.value?.split(",") ?? [];
}

async function initateTags(select) {
  const projectId = document.querySelector("main").getAttribute("project-id");
  const response = await fetch("/ShowTicket/ShowTicket/getTags", {
    method: "POST",
    body: JSON.stringify({ projectId }),
    headers: {
      "Content-Type": "application/json",
    },
  });

  if (!response.ok) {
    throw new Error(response.statusText);
  }
  const { tags } = await response.json();
  const selectedTags = getTagsFromDom();
  initateTomSelect(select, tags, selectedTags);
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
    .catch(() => {})
    .finally(() => stopSpinner());
}

async function saveDateToFinish(input, defaultValue, id = null) {
  const { value } = input;
  const { original: ticket = {}, error } = await saveTicket(
    formatDate(value),
    "dateToFinish",
    id,
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

function arraysAreEqual(arr1, arr2) {
  return JSON.stringify(arr1) === JSON.stringify(arr2);
}

document.addEventListener("DOMContentLoaded", function () {
  // Some default values, if there is a save error.
  const descriptionDefaultValue = tinymce?.activeEditor?.getContent();
  const statusDefaultValue = document.getElementById("status-select")?.value;
  const priorityDefaultValue =
    document.getElementById("priority-select")?.value;
  const userDefaultValue = document.getElementById("user-select")?.value;
  let select = null;
  const { id } = document.querySelector("main");

  if (id) {
    select = new TomSelect("#tags-select", {
      options: [],
      create: true,
      persist: false,
      maxItems: null,
    });

    document.querySelector(".ts-wrapper").style.display = "none";
    initateTags(select);
    select.on("change", async function (values) {
      const defaultValue = getTagsFromDom();
      if (!arraysAreEqual(defaultValue, values)) {
        const { original: ticket = {}, error } = await saveTicket(
          values.join(","),
          "tags",
        );

        if (error) {
          saveError(document.querySelector(".ts-control"));
          select.setValue(defaultValue);
        } else if (ticket) {
          saveSuccess(document.querySelector(".ts-control"));
        }
      }
    });
  }

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

  // Delete modal
  const confirmDeleteButton = document.querySelector(".confirm-delete");
  const cancelDeleteButton = document.querySelector(".cancel-delete");
  const deleteModal = window["delete-modal"];

  window["delete-ticket"]?.addEventListener("click", () => {
    deleteModal.style.display = "block";
  });

  document.addEventListener("click", function ({ target }) {
    // To make click outside of the modal close the modal.
    if (
      deleteModal.style.display === "block" &&
      !deleteModal.querySelector(".modal-content").contains(target) &&
      target.id === "delete-modal"
    ) {
      deleteModal.style.display = "none";
    }
  });

  cancelDeleteButton?.addEventListener("click", () => {
    deleteModal.style.display = "none";
  });

  confirmDeleteButton?.addEventListener("click", () => {
    deleteModal.style.display = "none";
    deleteTicket();
  });

  // Event listeners
  // Buttons in top bar
  window["copy-url-button"]?.addEventListener("click", function () {
    copyCurrentUrl();
  });

  // The following are much alike, and is change in the different inputs
  window["headline-input"]?.addEventListener("change", function () {
    const input = window["headline-input"];
    simpleSaveTicketWrapper(input, "headline");
  });

  window["sprint-select"]?.addEventListener("change", function () {
    const input = window["sprint-select"];
    simpleSaveTicketWrapper(input, "sprint");
  });

  window["milestone-select"]?.addEventListener("change", function () {
    const input = window["milestone-select"];
    simpleSaveTicketWrapper(input, "milestoneid");
  });

  window["related-tickets-select"]?.addEventListener("change", function () {
    const input = window["related-tickets-select"];
    simpleSaveTicketWrapper(input, "dependingTicketId");
  });

  window["status-select"]?.addEventListener("change", function () {
    const input = window["status-select"];
    simpleSaveTicketWrapper(input, "status", statusDefaultValue);
  });

  window["priority-select"]?.addEventListener("change", function () {
    const input = window["priority-select"];
    simpleSaveTicketWrapper(input, "priority", priorityDefaultValue);
  });

  window["date-to-finish-input"]?.addEventListener("change", function () {
    const input = window["date-to-finish-input"];

    saveDateToFinish(input, input.defaultValue);
  });

  window["plan-hours-input"]?.addEventListener("change", function () {
    const input = window["plan-hours-input"];
    simpleSaveTicketWrapper(input, "planHours");
  });

  window["user-select"]?.addEventListener("change", function () {
    const input = window["user-select"];
    simpleSaveTicketWrapper(input, "editorId", userDefaultValue);
  });

  // Get all elements with ids that start with subtask and is followed by *some* numbers.
  const subtasksIds = Array.from(document.querySelectorAll('[id^="subtask-"]'))
    .filter(({ id }) => /^subtask-\d+$/.test(id))
    .map(({ id }) => id);
  // Add event listeners to sub task children, this could potentially be a lot, I don't know
  // If users a prone to subtasks in leantime. Perhaps we should limit this some time.
  let subtaskDefaultValues = {};
  subtasksIds.forEach((subtaskId) => {
    const id = subtaskId.replace("subtask-", "");
    subtaskDefaultValues[`subtask-status-select-${id}`] =
      window[`subtask-status-select-${id}`].value;
    subtaskDefaultValues[`subtask-user-select-${id}`] =
      window[`subtask-user-select-${id}`].value;
    subtaskDefaultValues[`subtask-date-to-finish-input-${id}`] =
      window[`subtask-date-to-finish-input-${id}`].value;
    subtaskDefaultValues[`subtask-plan-hours-input-${id}`] =
      window[`subtask-plan-hours-input-${id}`].value;

    window[`subtask-status-select-${id}`].addEventListener(
      "change",
      function () {
        const input = window[`subtask-status-select-${id}`];
        simpleSaveTicketWrapper(
          input,
          "status",
          subtaskDefaultValues[`subtask-status-select-${id}`],
          id,
        );
      },
    );
    window[`subtask-user-select-${id}`].addEventListener("change", function () {
      const input = window[`subtask-user-select-${id}`];
      simpleSaveTicketWrapper(
        input,
        "editorId",
        subtaskDefaultValues[`subtask-user-select-${id}`],
        id,
      );
    });
    window[`subtask-date-to-finish-input-${id}`].addEventListener(
      "change",
      function () {
        const input = window[`subtask-date-to-finish-input-${id}`];
        saveDateToFinish(
          input,
          subtaskDefaultValues[`subtask-date-to-finish-input-${id}`],
          id,
        );
      },
    );
    window[`subtask-plan-hours-input-${id}`].addEventListener(
      "change",
      function () {
        const input = window[`subtask-plan-hours-input-${id}`];
        simpleSaveTicketWrapper(
          input,
          "planHours",
          subtaskDefaultValues[`subtask-plan-hours-input-${id}`],
          id,
        );
      },
    );
  });
});

// Spinner animation in top bar
function startSpinner() {
  window["spinner"].style.display = "flex";
}

function stopSpinner() {
  window["spinner"].style.display = "none";
}
