(function ($) {
  $(document).ready(function () {
    $("#show-result-history").on("click", function () {
      var $chartContainer = $("#chart-container");
      if ($chartContainer.is(":visible")) {
        $chartContainer.hide();
        return;
      }

      $chartContainer.show();
      $(this).remove();
      fetchChartData();
    });
  });

  function fetchChartData() {
    $.ajax({
      url: dataVisualizationAjax.ajax_url,
      type: "GET",
      data: {
        action: "get_chart_data",
        nonce: dataVisualizationAjax.nonce,
        exercises: dataVisualizationAjax.exercises,
      },
      success: function (response) {
        if (response.success) {
          createCharts(response.data);
        } else {
          console.error("Error fetching chart data");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", error);
      },
    });
  }

  function createCharts(data) {
    var commonOptions = {
      responsive: true,
      plugins: {
        tooltip: {
          callbacks: {
            label: function (context) {
              const point = context.raw;
              const formattedDate = new Date(point.date).toLocaleDateString();
              return `Date: ${formattedDate}, Result: ${point.y}`;
            },
          },
        },
      },
      scales: {
        x: {
          type: "linear",
          ticks: {
            stepSize: 1,
            callback: function (value, index, values) {
              return "Week " + Math.floor(value);
            },
          },
          title: {
            display: true,
            text: "Week of the Year",
          },
        },
      },
      backgroundColor: "white",
    };

    if (data.regular_chart_data.length > 0) {
      new Chart(document.getElementById("regularDataChart").getContext("2d"), {
        type: "scatter",
        data: {
          datasets: data.regular_chart_data,
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: {
              title: {
                display: true,
                text: "Result",
              },
            },
          },
        },
      });
    } else {
      document.getElementById("regularDataChart").style.display = "none";
    }

    if (data.time_chart_data.length > 0) {
      new Chart(document.getElementById("timeDataChart").getContext("2d"), {
        type: "scatter",
        data: {
          datasets: data.time_chart_data,
        },
        options: {
          ...commonOptions,
          scales: {
            ...commonOptions.scales,
            y: {
              ticks: {
                callback: function (value, index, values) {
                  var minutes = Math.floor(value);
                  var seconds = Math.round((value - minutes) * 60);
                  return minutes + ":" + (seconds < 10 ? "0" : "") + seconds;
                },
              },
              title: {
                display: true,
                text: "Time (minutes:seconds)",
              },
            },
          },
          plugins: {
            tooltip: {
              callbacks: {
                label: function (context) {
                  const point = context.raw;
                  const formattedDate = new Date(
                    point.date,
                  ).toLocaleDateString();
                  const minutes = Math.floor(point.y);
                  const seconds = Math.round((point.y - minutes) * 60);
                  const formattedTime = `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
                  return `Date: ${formattedDate}, Time: ${formattedTime}`;
                },
              },
            },
          },
        },
      });
    } else {
      document.getElementById("timeDataChart").style.display = "none";
    }
  }
})(jQuery);
