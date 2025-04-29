// Stupid leantime dates confusing me, maybe these formatting functions are correct, I sure hope so.
export function formatDate(date) {
  const localDate = new Date(date);
  const yyyy = localDate.getFullYear();
  let mm = localDate.getMonth() + 1; // Months start at 0!
  let dd = localDate.getDate();

  dd = dd < 10 ? `0${dd}` : dd;
  mm = mm < 10 ? `0${mm}` : mm;

  return dd + "/" + mm + "/" + yyyy;
}

function getTagsFromDom() {
  return window["selected-tags"]?.value?.split(",") ?? [];
}

function initateTomSelect(select, tags, selectedTags) {
  let mappedTags = mapDataForTomSelect(Object.values(tags));
  window["skeleton-input"].style.display = "none";
  document.querySelector(".ts-wrapper").style.display = "";
  select.addOptions(mappedTags);
  select.setValue(selectedTags);
}

export async function initateTags(select) {
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

export function formatDateToDatetimeInput(date) {
  const localDate = new Date(date);
  const yyyy = localDate.getFullYear();
  let mm = localDate.getMonth() + 1; // Months start at 0!
  let dd = localDate.getDate();

  dd = dd < 10 ? `0${dd}` : dd;
  mm = mm < 10 ? `0${mm}` : mm;

  return yyyy + "-" + mm + "-" + dd;
}

export function mapDataForTomSelect(inputArray) {
  return inputArray.map((data) => {
    return { value: data, text: data };
  });
}

// Save animations from project overview
export function saveSuccess(elem) {
  elem.classList.add("save-success");

  setTimeout(() => {
    elem.classList.remove("save-success");
  }, 1000);
}

export function saveError(elem) {
  elem.classList.add("save-error");

  setTimeout(() => {
    elem.classList.remove("save-error");
  }, 1000);
}

export async function copyCurrentUrl() {
  const url = document.location.href;
  const button = window["copy-url-button"];

  try {
    await navigator.clipboard.writeText(url);
    saveSuccess(button);
  } catch (error) {
    saveError(button);
  }
}
