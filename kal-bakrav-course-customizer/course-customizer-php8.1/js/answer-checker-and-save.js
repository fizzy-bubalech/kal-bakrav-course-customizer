document.addEventListener("DOMContentLoaded", function () {
  console.log("Script loaded", new Date().toISOString());

  window.CourseCustomizer = {
    validAnswers: {},
    currentAnswer: null,
    exerciseVariables: {},
    questionsIsTime: {},
  };

  function disableAllQuizNavigationButtons() {
    const backButtons = document.querySelectorAll(
      '.wpProQuiz_button[name="back"]',
    );
    const nextButtons = document.querySelectorAll(
      '.wpProQuiz_button[name="next"]',
    );

    backButtons.forEach((button) => {
      button.disabled = true;
    });

    nextButtons.forEach((button) => {
      button.disabled = true;
    });
  }

  disableAllQuizNavigationButtons();

  function getExerciseVariables() {
    fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "get_db_variables",
        nonce: myAjax.nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          CourseCustomizer.exerciseVariables = data.data;
        } else {
          console.error("Failed to load exercise variables:", data.error);
        }
      })
      .catch((error) => {
        console.error("Error loading exercise variables:", error);
      });
  }

  function getQuestionsIsTime() {
    let quizMeta = document
      .querySelector(".wpProQuiz_content")
      .getAttribute("data-quiz-meta");
    quizMeta = JSON.parse(quizMeta);
    let quizId = quizMeta["quiz_pro_id"];

    fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "get_questions_is_time",
        nonce: myAjax.nonce,
        quiz_id: quizId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          CourseCustomizer.questionsIsTime = data.data;
        } else {
          console.error("Failed to load questions IsTime:", data.error);
        }
      })
      .catch((error) => {
        console.error("Error loading questions IsTime:", error);
      });
  }

  function handleKeyStroke(e) {
    let activeQuestionItem = document.querySelector(
      '.wpProQuiz_listItem:not([style*="display: none"])',
    );
    if (!activeQuestionItem) {
      return;
    }

    let questionMeta = activeQuestionItem.getAttribute("data-question-meta");
    questionMeta = JSON.parse(questionMeta);
    let questionId = questionMeta["question_pro_id"];
    const button = activeQuestionItem.querySelector(
      '.wpProQuiz_button:not([style*="display: none"]',
    );

    const validationResult = validateAndStoreAnswer(questionId);

    const inputElement = activeQuestionItem.querySelector(
      'input[type="text"], textarea',
    );
    if (hasAnswerChanged(activeQuestionItem)) {
      const validationResult = validateAndStoreAnswer(questionId);

      const inputElement = activeQuestionItem.querySelector(
        'input[type="text"], textarea',
      );
      if (validationResult === true) {
        showAnswerPopup(inputElement, "Valid answer", true);
      } else {
        showAnswerPopup(inputElement, validationResult, false);
      }

      toggleProceedButtonVisibility(button, validationResult === true);
    }
  }

  function hasAnswerChanged(questionItem) {
    const newAnswer = getAnswerFromQuestionItem(questionItem);

    if (newAnswer !== CourseCustomizer.currentAnswer) {
      CourseCustomizer.currentAnswer = newAnswer;
      return true;
    }
    return false;
  }

  function getAnswerFromQuestionItem(questionItem) {
    const questionType = questionItem.dataset.type;
    let answer;

    switch (questionType) {
      case "single":
        const checkedRadio = questionItem.querySelector(
          'input[type="radio"]:checked',
        );
        answer = checkedRadio ? checkedRadio.value : null;
        break;
      case "multiple":
        answer = Array.from(
          questionItem.querySelectorAll('input[type="checkbox"]:checked'),
        ).map((cb) => cb.value);
        break;
      case "free_answer":
      case "essay":
        const input = questionItem.querySelector(
          'input[type="text"], textarea',
        );
        answer = input ? input.value : null;
        break;
      default:
        console.error("Unknown question type:", questionType);
        answer = null;
    }

    return answer;
  }

  function showAnswerPopup(inputElement, message, isValid) {
    // Remove any existing popup
    const existingPopup = document.getElementById("answerPopup");
    if (existingPopup) existingPopup.remove();

    // Create popup element
    const popup = document.createElement("div");
    popup.id = "answerPopup";
    popup.textContent = message;
    popup.style.cssText = `
      position: absolute;
      color: white;
      padding: 10px;
      border-radius: 5px;
      font-size: 14px;
      z-index: 1000;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    `;

    // Set color based on validity
    popup.style.backgroundColor = isValid ? "#4CAF50" : "#ff6b6b";

    // Position the popup
    const rect = inputElement.getBoundingClientRect();
    popup.style.top = `${rect.bottom + window.scrollY + 5}px`;
    popup.style.left = `${rect.left + window.scrollX}px`;

    // Add popup to the body
    document.body.appendChild(popup);

    // Remove popup after 3 seconds
    setTimeout(() => {
      popup.remove();
    }, 3000);
  }

  function isAnswerValid(answer, isTime) {
    if (isTime) {
      if (typeof answer !== "string") return "Invalid input type";
      answer = answer.trim();
      const timeRegex = /^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/;
      if (!timeRegex.test(answer)) return "Invalid time format";
      const parts = answer.split(":");
      if (parts.length < 2)
        return "Time must include at least minutes and seconds";
    } else {
      if (typeof answer === "string") {
        answer = answer.trim();
        if (!/^\d+$/.test(answer)) return "Must be a whole number";
      }
      const numAnswer = Number(answer);
      if (isNaN(numAnswer) || !Number.isInteger(numAnswer))
        return "Must be a whole number";
      if (numAnswer < 1 || numAnswer > 9999)
        return "Number must be between 1 and 9999";
    }
    return true; // Valid answer
  }

  function validateAndStoreAnswer(questionId) {
    const answer = CourseCustomizer.currentAnswer;
    let isTime = CourseCustomizer.questionsIsTime[questionId];
    isTime = isTime["is_time"];
    if (isTime === "0") isTime = false;
    if (isTime === "1") isTime = true;

    const validationResult = isAnswerValid(answer, isTime);
    if (validationResult === true) {
      storeValidAnswer(answer, questionId);
    }
    return validationResult;
  }
  function storeValidAnswer(answer, questionId) {
    CourseCustomizer.validAnswers[questionId] = answer;
  }

  function toggleProceedButtonVisibility(button, isValid) {
    if (button) {
      button.disabled = !isValid;
    } else {
    }
  }

  function handleButtonClick(e) {
    if (!e.target.classList.contains("wpProQuiz_button")) return;

    if (
      e.target.value === "Finish Quiz" ||
      e.target.textContent.trim() === "Finish Quiz" ||
      e.target.textContent.trim() === "סיים מבחן" ||
      e.target.value === "סיים מבחן"
    ) {
      submitAllAnswers();
    } else if (
      e.target.value === "back" ||
      e.target.textContent.trim() === "back" ||
      e.target.name === "back"
    ) {
      CourseCustomizer.currentAnswer = null;
      return;
    } else {
      CourseCustomizer.currentAnswer = null;
      return;
    }
  }

  function submitAllAnswers() {
    fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "save_quiz_results",
        quizData: JSON.stringify(CourseCustomizer.validAnswers),
        nonce: myAjax.nonce,
      }),
    })
      .then((response) => {
        return response.json();
      })
      .then((data) => {
        if (data.success) {
          alert("Quiz submitted successfully!");
        } else {
          console.error("Error details:", data.error);
          showPopupErrorAndReload();
        }
      })
      .catch((error) => {
        console.error("Quiz submission error:", error);
        console.error("Error details:", error.message, error.stack);
        showPopupErrorAndReload();
      });
  }

  function showPopupErrorAndReload() {
    alert("There was an error. Please try again.");
    location.reload();
  }

  // Initialize
  getExerciseVariables();
  getQuestionsIsTime();

  // Add event listeners
  document.addEventListener("keyup", handleKeyStroke);
  document.addEventListener("click", handleButtonClick);
});
