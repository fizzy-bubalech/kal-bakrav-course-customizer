jQuery(document).ready(function ($) {
  const resultsTable = $("#user-results-table");
  const applyFiltersButton = $("#apply-filters");
  let orderBy = "result_date";
  let order = "DESC";
  let currentPage = 1;
  let totalPages = 1;
  let totalItems = 0;
  const perPage = 10;

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

  const loadResults = (page = 1) => {
    const data = {
      action: "get_user_filtered_results",
      nonce: user_results_ajax.nonce,
      exercise_id: $("#filter-exercise").val(),
      search: $("#search-input").val(),
      order_by: orderBy,
      order: order,
      page: page,
    };

    $.ajax({
      url: user_results_ajax.ajax_url,
      data: data,
      dataType: "json",
      success: (response) => {
        console.log("AJAX Response:", response);
        if (response.success && Array.isArray(response.data.results)) {
          updateTable(response.data.results);
          updateSortIndicators();
          updatePagination(response.data.total_count, page);
        } else {
          console.error(
            "Error in AJAX response or invalid data format:",
            response,
          );
          showError(
            "Failed to load results. Please check the console for details.",
          );
        }
      },
      error: (xhr, status, error) => {
        console.error("AJAX request failed:", status, error);
        showError(
          "Failed to load results. Please check the console for details.",
        );
      },
    });
  };

  const updateTable = (results) => {
    const tbody = resultsTable.find("tbody");
    tbody.empty();

    if (results.length === 0) {
      tbody.append('<tr><td colspan="4">No results found</td></tr>');
      return;
    }

    const rows = results
      .map(
        (result) => `
                <tr>
                    <td>${escapeHtml(result.exercise_name)}</td>
                    <td>${formatResult(result.result, result.is_time == 1)}</td>
                    <td>${result.result_date}</td>
                    <td>${result.is_metric == 1 ? "Yes" : "No"}</td>
                </tr>
            `,
      )
      .join("");

    tbody.html(rows);
  };

  const updateSortIndicators = () => {
    resultsTable.find("th").removeClass("sorted asc desc");
    const currentSortColumn = resultsTable.find(`th[data-sort="${orderBy}"]`);
    currentSortColumn.addClass("sorted " + order.toLowerCase());
    currentSortColumn.find(".sorting-indicator").css("display", "inline-block");
  };

  const updatePagination = (totalCount, currentPage) => {
    totalItems = parseInt(totalCount);
    totalPages = Math.ceil(totalItems / perPage);
    $(".displaying-num").text(`${totalItems} items`);
    $(".total-pages").text(totalPages);
    $("#current-page-selector").val(currentPage);

    $(".first-page, .prev-page, .next-page, .last-page").removeClass(
      "disabled",
    );

    if (currentPage === 1) {
      $(".first-page, .prev-page").addClass("disabled");
    }
    if (currentPage === totalPages) {
      $(".next-page, .last-page").addClass("disabled");
    }
  };

  const showError = (message) => {
    $("#message-container")
      .html(`<div class="error">${message}</div>`)
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
    currentPage = 1;
    loadResults(currentPage);
  });

  resultsTable.on("click", "th.sortable", function () {
    const newOrderBy = $(this).data("sort");
    if (newOrderBy === orderBy) {
      order = order === "ASC" ? "DESC" : "ASC";
    } else {
      orderBy = newOrderBy;
      order = "ASC";
    }
    currentPage = 1;
    loadResults(currentPage);
  });

  $(".first-page").on("click", function (e) {
    e.preventDefault();
    if (currentPage !== 1) {
      currentPage = 1;
      loadResults(currentPage);
    }
  });

  $(".prev-page").on("click", function (e) {
    e.preventDefault();
    if (currentPage > 1) {
      currentPage--;
      loadResults(currentPage);
    }
  });

  $(".next-page").on("click", function (e) {
    e.preventDefault();
    if (currentPage < totalPages) {
      currentPage++;
      loadResults(currentPage);
    }
  });

  $(".last-page").on("click", function (e) {
    e.preventDefault();
    if (currentPage !== totalPages) {
      currentPage = totalPages;
      loadResults(currentPage);
    }
  });

  $("#current-page-selector").on("keydown", function (e) {
    if (e.keyCode === 13) {
      e.preventDefault();
      let newPage = parseInt($(this).val());
      if (isNaN(newPage)) {
        newPage = 1;
      }
      newPage = Math.min(Math.max(1, newPage), totalPages);
      if (newPage !== currentPage) {
        currentPage = newPage;
        loadResults(currentPage);
      }
    }
  });

  // Initial load
  loadResults(currentPage);
});
