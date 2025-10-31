jQuery(document).ready(function ($) {
  const resultsTable = $("#results-table");
  const filterForm = $("#filter-form");
  const applyFiltersButton = $("#apply-filters");
  let orderBy = "result_date";
  let order = "DESC";

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

  const loadResults = () => {
    const data = {
      action: "get_filtered_results",
      nonce: course_customizer_ajax.nonce,
      user_id: $("#filter-user").val(),
      exercise_id: $("#filter-exercise").val(),
      search: $("#search-input").val(),
      order_by: orderBy,
      order: order,
    };

    $.ajax({
      url: course_customizer_ajax.ajax_url,
      data: data,
      dataType: "json",
      success: (response) => {
        updateTable(response);
        updateSortIndicators();
      },
      error: (xhr, status, error) => {
        console.error("AJAX request failed:", status, error);
        showError("Failed to load results. Please try again.");
      },
    });
  };

  const updateTable = (results) => {
    const tbody = resultsTable.find("tbody");
    tbody.empty();

    if (results.length === 0) {
      tbody.append('<tr><td colspan="7">No results found</td></tr>');
      return;
    }

    const rows = results
      .map(
        (result) => `
        <tr>
          <td>${result.result_id}</td>
          <td>${escapeHtml(result.user_name)}</td>
          <td>${escapeHtml(result.exercise_name)}</td>
          <td>${formatResult(result.result, result.is_time == 1)}</td>
          <td>${result.result_date}</td>
          <td>${result.is_metric == 1 ? "Yes" : "No"}</td>
          <td><button class="delete-result" data-id="${result.result_id}">Delete</button></td>
        </tr>
      `,
      )
      .join("");

    tbody.html(rows);
  };

  const updateSortIndicators = () => {
    resultsTable.find("th").removeClass("sort-asc sort-desc");
    const currentSortColumn = resultsTable.find(`th[data-sort="${orderBy}"]`);
    currentSortColumn.addClass(
      order.toLowerCase() === "asc" ? "sort-asc" : "sort-desc",
    );
  };

  const deleteResult = (resultId) => {
    $.post(
      course_customizer_ajax.ajax_url,
      {
        action: "delete_result",
        nonce: course_customizer_ajax.nonce,
        result_id: resultId,
      },
      (response) => {
        if (response.success) {
          loadResults();
          showSuccess("Result deleted successfully.");
        } else {
          showError("Failed to delete result. Please try again.");
        }
      },
    ).fail(() => {
      showError("An error occurred while deleting the result.");
    });
  };

  const showError = (message) => {
    $("#message-container")
      .html(`<div class="error">${message}</div>`)
      .show()
      .delay(3000)
      .fadeOut();
  };

  const showSuccess = (message) => {
    $("#message-container")
      .html(`<div class="success">${message}</div>`)
      .show()
      .delay(3000)
      .fadeOut();
  };

  const escapeHtml = (unsafe) => {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  };

  // Event Listeners
  applyFiltersButton.on("click", (e) => {
    e.preventDefault();
    loadResults();
  });

  resultsTable.on("click", ".sortable", function () {
    const newOrderBy = $(this).data("sort");
    order = newOrderBy === orderBy ? (order === "ASC" ? "DESC" : "ASC") : "ASC";
    orderBy = newOrderBy;
    loadResults();
  });

  resultsTable.on("click", ".delete-result", function () {
    const resultId = $(this).data("id");
    if (confirm("Are you sure you want to delete this result?")) {
      deleteResult(resultId);
    }
  });

  // Initial load
  loadResults();
});
