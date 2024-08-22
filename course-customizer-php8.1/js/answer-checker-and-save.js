document.addEventListener("DOMContentLoaded", function () {
  console.log("Script loaded", new Date().toISOString());

  window.CourseCustomizer = {};

  CourseCustomizer.validAnswers = [];

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
      CourseCustomizer.pressedFinished = false;
      return;
    }

    if (
      e.target.value === "Finish Quiz" ||
      e.target.textContent.trim() === "Finish Quiz"
    ) {
      console.log("Finish Quiz button clicked");
      CourseCustomizer.pressedFinished = true;
    } else {
      CourseCustomizer.pressedFinished = false;
    }
    let capturedData = captureQuestionData();
    if (!capturedData) {
      console.error("Failed to capture question data");
      return;
    }

    CourseCustomizer.lastCapturedQuizData = capturedData;

    console.log("Captured data:", capturedData);
  }

  document.addEventListener("click", interceptButtonClick, true);

  function handleButtonClick(e) {
    if (!CourseCustomizer.lastCapturedQuizData) {
      console.error("No captured data available");
      return;
    }

    let { userAnswer, questionMeta } = CourseCustomizer.lastCapturedQuizData;

    console.log("Captured quiz data:", CourseCustomizer.lastCapturedQuizData);

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

    validateAndStoreAnswer(
      userAnswer,
      questionMeta.question_post_id,
      questionMeta.question_pro_id,
    );

    CourseCustomizer.lastCapturedQuizData = null;
  }

  function validateAndStoreAnswer(userAnswer, postQuestionId, questionId) {
    console.log(
      "Validating answer:",
      userAnswer,
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
        userAnswer: userAnswer,
        question_id: questionId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Validation response:", data);
        if (data.success && data.data.is_valid) {
          console.log("Answer is valid");
          let questionData = { userAnswer: userAnswer, questionId: questionId };
          storeValidAnswer(questionData);
        } else {
          console.error("Invalid answer");
          showPopupErrorAndReload();
        }
        if (
          CourseCustomizer.pressedFinished === true &&
          data.success &&
          data.data.is_valid
        ) {
          submitAllAnswers();
        }
      })
      .catch((error) => {
        console.error("AJAX error:", error);
        showPopupErrorAndReload();
      });
  }

  function storeValidAnswer(questionData) {
    CourseCustomizer.validAnswers.push(questionData);
  }

  function submitAllAnswers() {
    console.log(
      "Submitting all answers:",
      JSON.stringify(CourseCustomizer.validAnswers),
    );
    let validAnswers = JSON.stringify(CourseCustomizer.validAnswers);
    fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "save_quiz_results",
        quizData: validAnswers,
        nonce: myAjax.nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        console.log(
          "Submitting: " + CourseCustomizer.validAnswers.length + " answers.",
        );
        if (data.success) {
          alert("Quiz submitted successfully!");
        } else {
          alert("Failed to submit quiz. Please try again.");
          console.log(data);
          location.reload();
        }
      })
      .catch((error) => {
        console.error("Quiz submission error:", error);
        alert("An error occurred. Please try again.");
      });
  }

  function showPopupErrorAndReload() {
    alert("Your answer was invalid. Please try again.");
    location.reload();
  }

  function attachButtonHandlers() {
    document.querySelectorAll(".wpProQuiz_button").forEach((button) => {
      button.removeEventListener("click", handleButtonClick);
      button.addEventListener("click", handleButtonClick);
    });
  }
  attachButtonHandlers();
});
