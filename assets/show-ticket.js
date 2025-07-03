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

let subtaskDefaultValues = {};

function getElementIdsWithPrefix(prefix) {
  const regex = new RegExp(`^${prefix}\\d+$`);
  return Array.from(document.querySelectorAll(`[id^="${prefix}"]`))
    .filter(({ id }) => regex.test(id))
    .map(({ id }) => id);
}

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

function deleteComment(id, input) {
  startSpinner();
  fetch("/ShowTicket/ShowTicket/deleteComment", {
    method: "POST",
    body: JSON.stringify({ id }),
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => {
      response.json();
    })
    .then(() => {
      saveSuccess(input);
      setTimeout(() => {
        window[`comment-${id}`].remove();
      }, 1000);
    })
    .catch(() => {
      saveError(input);
    })
    .finally(() => stopSpinner());
}

function updateCommentText(id, text) {
  fetch("/ShowTicket/ShowTicket/editComment", {
    method: "POST",
    body: JSON.stringify({ id, text }),
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => {
      response.json();
    })
    .then(() => {
      window[`comment-text-${id}`].innerHTML = text;
    });
}

function replyToComment(id, text) {
  fetch("/ShowTicket/ShowTicket/replyToComment", {
    method: "POST",
    body: JSON.stringify({ father: id, text: text }),
    headers: {
      "Content-Type": "application/json",
    },
  })
    .then((response) => {
      response.json();
    })
    .then(() => {
      location.reload();
    });
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

async function createSubTicket(input) {
  startSpinner();
  try {
    const response = await fetch("/ShowTicket/CreateTicket/createTicket", {
      method: "POST",
      body: JSON.stringify({ input }),
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(response.statusText);
    }

    const { ticket } = await response.json();
    stopSpinner();
    return { ticketId: ticket };
  } catch (error) {
    stopSpinner();
    return { error: true, errorText: error };
  }
}

function arraysAreEqual(arr1, arr2) {
  return JSON.stringify(arr1) === JSON.stringify(arr2);
}

document.addEventListener("DOMContentLoaded", function () {
  window["new-subtask-input-title"]?.addEventListener("input", () => {
    if (window["new-subtask-input-title"].value?.length > 0) {
      window["save-sub-ticket-button"].removeAttribute("disabled");
    }
  });

  window["save-sub-ticket-button"]?.addEventListener(
    "click",
    async function () {
      const projectId = document
        .querySelector("main")
        .getAttribute("project-id");
      const { id } = document.querySelector("main");

      const saveObject = {
        headline: window["new-subtask-input-title"].value,
        dependingTicketId: id, // this is where it becomes a sub task
        status: window["new-subtask-status-label"].value,
        editorId: window["new-subtask-user-select-editor"].value,
        dateToFinish: window["new-subtask-date-to-finish-input"].value
          ? formatDate(window["new-subtask-date-to-finish-input"].value)
          : "",
        projectId: projectId,
        planHours: window["new-subtask-plan-hours-input"].value,
      };
      const { ticketId, error, errorText } = await createSubTicket(saveObject);
      if (error) {
        // todo show error
        console.error(errorText ?? "Unknown error");
      } else {
        // If task is created, it will be added to the dom
        const subtask = window["next-sub-task"];
        subtask.id = `subtask-${ticketId}`;

        // Fill out new task
        subtask.querySelector("#new-sub-task-id").textContent = ticketId;
        subtask
          .querySelector("#new-sub-task-id")
          .setAttribute("href", `/ShowTicket/ShowTicket?ticketId=${ticketId}`);
        subtask.querySelector("#new-sub-task-id").id = "";
        subtask.querySelector("#next-sub-task-input-title").value =
          window["new-subtask-input-title"].value;
        subtask.querySelector("#next-sub-task-status-label").value =
          window["new-subtask-status-label"].value;
        subtask.querySelector("#next-sub-task-status-label").id =
          `subtask-status-select-${ticketId}`;
        subtask.querySelector("#next-sub-task-user-select-editor").value =
          window["new-subtask-user-select-editor"].value;
        subtask.querySelector("#next-sub-task-user-select-editor").id =
          `subtask-user-select--${ticketId}`;
        subtask.querySelector("#next-sub-task-date-to-finish-input").value =
          window["new-subtask-date-to-finish-input"].value;
        subtask.querySelector("#next-sub-task-date-to-finish-input").id =
          `subtask-date-to-finish-input-${ticketId}`;

        subtask.querySelector("#next-sub-task-plan-hours-input").value =
          window["new-subtask-plan-hours-input"].value;
        subtask.querySelector("#next-sub-task-plan-hours-input").id =
          `subtask-plan-hours-input-${ticketId}`;

        // Reset values
        window["new-subtask-input-title"].value = "";
        window["new-subtask-status-label"].value = "";
        window["new-subtask-user-select-editor"].value = "";
        window["new-subtask-date-to-finish-input"].value = "";
        window["new-subtask-plan-hours-input"].value = "";

        initializeSubtaskInputs(`subtask-${ticketId}`, subtaskDefaultValues);

        const container = window["sub-tasks"];

        if (subtask && container) {
          subtask.style.display = "";

          const firstChild = container.firstElementChild;
          if (firstChild) {
            container.insertBefore(subtask, firstChild.nextSibling);
          } else {
            container.appendChild(subtask);
          }
        }
      }
    },
  );

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

  // Edit comment modal
  const confirmEditButton = document.querySelector(".confirm-edit");
  const cancelEditButton = document.querySelector(".cancel-edit");
  const editComment = window["edit-comment-modal"];

  document.addEventListener("click", function ({ target }) {
    // To make click outside of the modal close the modal.
    if (
      editComment.style.display === "block" &&
      !editComment.querySelector(".modal-content").contains(target) &&
      target.id === "edit-comment-modal"
    ) {
      editComment.style.display = "none";
    }
  });

  cancelEditButton?.addEventListener("click", () => {
    editComment.style.display = "none";
  });

  confirmEditButton?.addEventListener("click", () => {
    editComment.style.display = "none";

    const content = tinymce.get("comment-input").getContent();
    const sanitizedContent = DOMPurify.sanitize(content);

    updateCommentText(editComment.getAttribute("data-id"), sanitizedContent);
  });

  // reply comment modal
  const confirmReplyButton = document.querySelector(".confirm-reply");
  const cancelReplyButton = document.querySelector(".cancel-reply");
  const replyComment = window["reply-comment-modal"];

  document.addEventListener("click", function ({ target }) {
    // To make click outside of the modal close the modal.
    if (
      replyComment.style.display === "block" &&
      !replyComment.querySelector(".modal-content").contains(target) &&
      target.id === "reply-comment-modal"
    ) {
      tinymce.get("reply-input").setContent("");
      replyComment.style.display = "none";
    }
  });

  cancelReplyButton?.addEventListener("click", () => {
    replyComment.style.display = "none";
    tinymce.get("reply-input").setContent("");
  });

  confirmReplyButton?.addEventListener("click", () => {
    replyComment.style.display = "none";

    const content = tinymce.get("reply-input").getContent();
    tinymce.get("reply-input").setContent("");
    const sanitizedContent = DOMPurify.sanitize(content);
    replyToComment(replyComment.getAttribute("data-id"), sanitizedContent);
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

  // Tabs
  window["tab-comments"]?.addEventListener("click", function () {
    window["tab-comments"].ariaSelected = "true";
    window["comments-content"].classList.add("active");
    window["tab-logs"].ariaSelected = "false";
    window["worklog-content"].classList.remove("active");
  });

  window["tab-logs"]?.addEventListener("click", function () {
    window["tab-logs"].ariaSelected = "true";
    window["worklog-content"].classList.add("active");
    window["comments-content"].classList.remove("active");
    window["tab-comments"].ariaSelected = "false";
  });

  // Get all elements with ids that start with delete-comment- and is followed by *some* numbers (which is an id).
  const commentIds = getElementIdsWithPrefix("delete-comment-");
  commentIds.forEach((subtaskId) => {
    const id = subtaskId.replace("delete-comment-", "");

    window[`delete-comment-${id}`].addEventListener("click", function () {
      deleteComment(id, window[`comment-${id}`]);
    });
    window[`reply-to-comment-${id}`]?.addEventListener("click", function () {
      replyComment.style.display = "block";
      replyComment.setAttribute("data-id", id);
      // tinyMCE for rich text description edit
      tinymce.init({
        selector: "#reply-input",
        plugins: "link table code",
        toolbar:
          "undo redo | formatselect | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | code",
        height: 300,
        branding: false,
        skin: false,
        content_css: false,
        license_key: "gpl",
      });
    });
    window[`edit-comment-${id}`].addEventListener("click", function () {
      editComment.style.display = "block";
      editComment.setAttribute("data-id", id);
      // tinyMCE for rich text description edit
      tinymce.init({
        selector: "#comment-input",
        plugins: "link table code",
        toolbar:
          "undo redo | formatselect | bold italic underline | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | code",
        height: 300,
        branding: false,
        skin: false,
        content_css: false,
        license_key: "gpl",
      });
      setTimeout(() => {
        tinymce
          .get("comment-input")
          .setContent(window[`comment-text-${id}`].innerHTML);
      }, 0);
    });
  });

  function initializeSubtaskInputs(subtaskId, subtaskDefaultValues) {
    const id = subtaskId.replace("subtask-", "");

    const fields = [
      {
        key: "title-input",
        handler: simpleSaveTicketWrapper,
        fieldName: "title",
      },
      {
        key: "status-select",
        handler: simpleSaveTicketWrapper,
        fieldName: "status",
      },
      {
        key: "user-select",
        handler: simpleSaveTicketWrapper,
        fieldName: "editorId",
      },
      { key: "date-to-finish-input", handler: saveDateToFinish },
      {
        key: "plan-hours-input",
        handler: simpleSaveTicketWrapper,
        fieldName: "planHours",
      },
    ];

    fields.forEach(({ key, handler, fieldName }) => {
      const inputKey = `subtask-${key}-${id}`;
      const inputElement = window[inputKey];
      if (!inputElement) return;

      // Save the default value
      subtaskDefaultValues[inputKey] = inputElement.value;

      // Set up change listener
      inputElement.addEventListener("change", function () {
        const previousValue = subtaskDefaultValues[inputKey];
        if (fieldName) {
          handler(inputElement, fieldName, previousValue, id);
        } else {
          handler(inputElement, previousValue, id); // For saveDateToFinish
        }
      });
    });
  }

  // Get all elements with ids that start with subtask and is followed by *some* numbers (which is an id).
  const subtasksIds = getElementIdsWithPrefix("subtask-");

  // Add event listeners to sub task children, this could potentially be a lot, I don't know
  // If users a prone to subtasks in leantime. Perhaps we should limit this some time.
  subtasksIds.forEach((subtaskId) => {
    initializeSubtaskInputs(subtaskId, subtaskDefaultValues);
  });
});

// Spinner animation in top bar
function startSpinner() {
  window["spinner"].style.display = "flex";
}

function stopSpinner() {
  window["spinner"].style.display = "none";
}
