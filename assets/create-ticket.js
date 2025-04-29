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
import { formatDate, copyCurrentUrl,initateTags } from "./helpers";

function redirectToShowTicket(ticketId) {
  window.location.assign(`/ShowTicket/ShowTicket?ticketId=${ticketId}`);
}

async function createTicket(input) {
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

document.addEventListener("DOMContentLoaded", function () {
  stopSpinner();
  let select = null;
  const projectId = document.querySelector("main").getAttribute("project-id");

  window["project-id"]?.addEventListener("change", function () {
    this.form.submit();
  });

  if (projectId) {
    select = new TomSelect("#tags-select", {
      options: [],
      create: true,
      persist: false,
      maxItems: null,
    });

    document.querySelector(".ts-wrapper").style.display = "none";
    initateTags(select);
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
  });

  window["save-ticket-button"]?.addEventListener("click", async function () {
    const saveObject = {
      headline: window["headline-input"].value,
      description: DOMPurify.sanitize(
        tinymce.get("description-input").getContent(),
      ),
      status: window["status-select"].value,
      editorId: window["user-select"].value,
      dateToFinish: window["date-to-finish-input"].value
        ? formatDate(window["date-to-finish-input"].value)
        : "",
      sprint: window["sprint-select"].value,
      projectId: projectId,
      planHours: window["plan-hours-input"].value,
      tags: select.getValue().value,
      priority: window["priority-select"].value,
      milestoneid: window["milestone-select"].value,
    };

    const { ticketId, error, errorText } = await createTicket(saveObject);
    if (error) {
      // todo show error
      console.error(errorText ?? "Unknown error");
    } else {
      redirectToShowTicket(ticketId);
    }
  });

  // Buttons in top bar
  window["headline-input"]?.addEventListener("change", function () {
    window["save-ticket-button"].disabled =
      !window["headline-input"].value.length;
  });

  window["copy-url-button"]?.addEventListener("click", function () {
    copyCurrentUrl();
  });
});

// Spinner animation in top bar
function startSpinner() {
  window["notification"].style.display = "flex";
  window["spinner"].style.display = "flex";
}

function stopSpinner() {
  window["notification"].style.display = "none";
  window["spinner"].style.display = "none";
}
