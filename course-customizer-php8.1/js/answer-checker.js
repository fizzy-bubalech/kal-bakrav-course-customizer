document.addEventListener("DOMContentLoaded", function () {
  console.log("Script loaded", new Date().toISOString());

  function captureQuestionData() {
    let activeQuestionItem = document.querySelector(
      '.wpProQuiz_listItem:not([style*="display: none"])',
    );
    if (!activeQuestionItem) {
      console.error("No active question item found");
      return null;
    }

    let questionType = activeQuestionItem.dataset.type;
    console.log("Question type:", questionType);

    let userAnswer;

    switch (questionType) {
      case "single":
        let checkedRadio = activeQuestionItem.querySelector(
          'input[type="radio"]:checked',
        );
        userAnswer = checkedRadio
          ? checkedRadio.closest("label").textContent.trim()
          : null;
        break;
      case "multiple":
        userAnswer = Array.from(
          activeQuestionItem.querySelectorAll('input[type="checkbox"]:checked'),
        ).map((checkbox) => checkbox.closest("label").textContent.trim());
        break;
      case "free_answer":
      case "essay":
        let input = activeQuestionItem.querySelector(
          'input[type="text"], textarea',
        );
        userAnswer = input ? input.value : null;
        break;
      default:
        console.error("Unknown question type:", questionType);
        userAnswer = null;
    }

    let questionMeta;
    try {
      questionMeta = JSON.parse(
        activeQuestionItem.dataset.questionMeta ||
          activeQuestionItem.getAttribute("data-question-meta"),
      );
    } catch (error) {
      console.error("Error parsing question meta:", error);
      questionMeta = null;
    }

    if (!questionMeta) {
      console.error("Question meta is null or undefined");
    }

    return {
      userAnswer: userAnswer,
      questionType: questionType,
      questionMeta: questionMeta,
      activeQuestionItem: activeQuestionItem,
    };
  }

  function interceptButtonClick(e) {
    if (!e.target.classList.contains("wpProQuiz_button")) return;

    if (
      e.target.value === "Start Quiz" ||
      e.target.textContent.trim() === "Start Quiz"
    ) {
      console.log("Start Quiz button clicked");
      return;
    }

    console.log("Quiz button clicked, capturing data");
    let capturedData = captureQuestionData();
    if (!capturedData) {
      console.error("Failed to capture question data");
      return;
    }

    window.lastCapturedQuizData = capturedData;

    console.log("Captured data:", capturedData);
  }

  document.addEventListener("click", interceptButtonClick, true);

  function handleButtonClick(e) {
    if (!window.lastCapturedQuizData) {
      console.error("No captured data available");
      return;
    }

    let { userAnswer, questionMeta, activeQuestionItem } =
      window.lastCapturedQuizData;

    console.log("Captured quiz data:", window.lastCapturedQuizData);

    if (!questionMeta) {
      console.error("Question meta is null or undefined");
      showPopupErrorAndReload();
      return;
    }

    if (!questionMeta.question_post_id) {
      console.error("Missing question_post_id in question meta:", questionMeta);
      showPopupErrorAndReload();
      return;
    }

    isCorrectAnswer(userAnswer, questionMeta.question_post_id, e.target);

    window.lastCapturedQuizData = null;
  }

  function isCorrectAnswer(answer, postQuestionId, button) {
    console.log(
      "Validating answer:",
      answer,
      "for question ID:",
      postQuestionId,
    );

    fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "ajax_validate_quiz_answers",
        userAnswer: answer,
        post_question_id: postQuestionId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("AJAX response:", data);
        if (!(data.success && data.data.is_valid)) {
          showPopupErrorAndReload();
        } else {
          console.log("Answer is correct");
        }
      })
      .catch((error) => {
        console.error("AJAX error:", error);
        showPopupErrorAndReload();
      });
  }

  function showPopupErrorAndReload() {
    sessionStorage.setItem("quizReloaded", "true");
    location.reload();
    alert("Your answer was invalid.\nPlease try again.");
  }

  function attachButtonHandlers() {
    document.querySelectorAll(".wpProQuiz_button").forEach((button) => {
      button.removeEventListener("click", handleButtonClick);
      button.addEventListener("click", handleButtonClick);
    });
  }

  attachButtonHandlers();

  console.log("Initial quiz state:");
  console.log("Quiz items:", document.querySelectorAll(".wpProQuiz_listItem"));
  console.log("Quiz buttons:", document.querySelectorAll(".wpProQuiz_button"));
});
