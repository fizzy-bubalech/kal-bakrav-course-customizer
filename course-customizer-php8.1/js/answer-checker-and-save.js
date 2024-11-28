document.addEventListener("DOMContentLoaded", function () {
  function disableAllQuizNavigationButtons() {
    const backButtons = document.querySelectorAll(
      '.wpProQuiz_button[name="back"]'
    );
    const nextButtons = document.querySelectorAll(
      '.wpProQuiz_button[name="next"]'
    );

    const checkSingleButtons = document.querySelectorAll('.wpProQuiz_button[name="checkSingle"]');


    backButtons.forEach((button) => {
      button.disabled = true;
    });


    checkSingleButtons.forEach((button) => {
      button.disabled = true;
    });


    nextButtons.forEach((button) => {
      button.disabled = true;
    });
  }

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

  function getQuestionsExerciseProperties() {
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
        action: "get_questions_exercise_properties",
        nonce: myAjax.nonce,
        quiz_id: quizId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          CourseCustomizer.questionsExerciseProperties = data.data;
        } else {
          console.error("Failed to load questions IsTime:", data.error);
        }
      })
      .catch((error) => {
        console.error("Error loading questions IsTime:", error);
      });
  }


  window.CourseCustomizer = {
    validAnswers: {},
    currentAnswer: null,
    exerciseVariables: {},
    questionsIsTime: {},
    questionsAreRight: {},
    questionsExerciseProperties: {},
  };

  function attachQuestionListeners() {
    const questionItems = document.querySelectorAll('.wpProQuiz_listItem');
    questionItems.forEach(questionItem => {
      const questionMeta = JSON.parse(questionItem.getAttribute("data-question-meta"));
      const questionId = questionMeta["question_pro_id"];
      CourseCustomizer.questionsAreRight[questionId] = false;
      // Find the input/textarea within this question item
      const inputElement = questionItem.querySelector('input[type="text"], textarea');
      
      if (inputElement) {
        inputElement.addEventListener("keyup", (e) => handleQuestionKeyStroke(e, questionItem, questionId));
      }
    });
  }

  function handleQuestionKeyStroke(e, questionItem, questionId) {
    const button = document.querySelector('.wpProQuiz_QuestionButton:not([style*="display: none"]');


    if (hasAnswerChanged(questionItem)) {
      const validationResult = validateAndStoreAnswer(questionId);

      const inputElement = questionItem.querySelector('input[type="text"], textarea');
      if (validationResult === true) {
        showAnswerPopup(inputElement, "✓", true);
      } else {
        showAnswerPopup(inputElement, validationResult, false);
      }
      CourseCustomizer.questionsAreRight[questionId] = (validationResult === true);
      let valid = true;
      for(let key in CourseCustomizer.questionsAreRight){
        valid = valid && CourseCustomizer.questionsAreRight[key];

      }

      toggleProceedButtonVisibility(button, valid);
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
        const checkedRadio = questionItem.querySelector('input[type="radio"]:checked');
        answer = checkedRadio ? checkedRadio.value : null;
        break;
      case "multiple":
        answer = Array.from(questionItem.querySelectorAll('input[type="checkbox"]:checked'))
          .map((cb) => cb.value);
        break;
      case "free_answer":
      case "essay":
        const input = questionItem.querySelector('input[type="text"], textarea');
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

    popup.style.backgroundColor = isValid ? "#4CAF50" : "#ff6b6b";

    const rect = inputElement.getBoundingClientRect();
    popup.style.top = `${rect.bottom + window.scrollY + 5}px`;
    popup.style.left = `${rect.left + window.scrollX}px`;

    document.body.appendChild(popup);

    setTimeout(() => {
      popup.remove();
    }, 3000);
  }

  function isAnswerValid(answer, isTime,min = 1,max = 9999) {
    if (isTime) {
      if (typeof answer !== "string") return "Invalid input type";
      answer = answer.trim();
      const timeRegex = /^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/;
      let matches = answer.match(timeRegex);
      if (!matches) return "X";
      const [_, hours, minutes, seconds] = matches;
      if (!hours) return "X";
      let time = (hours ? parseInt(hours) : 0) *60*60 + (minutes ? parseInt(minutes) : 0) *60 +(parseInt(seconds));
      if (time < min | time > max)
        return "X";
    } else {
      if (typeof answer === "string") {
        answer = answer.trim();
        if (!/^\d+$/.test(answer)) return 'X';
      }
      const numAnswer = Number(answer);
      if (isNaN(numAnswer) || !Number.isInteger(numAnswer))
        return "X";

      if (numAnswer < min | numAnswer > max)
        return "X";
    }
    return true;
  }

  function validateAndStoreAnswer(questionId) {
    const answer = CourseCustomizer.currentAnswer;
    let questionExercise = CourseCustomizer.questionsExerciseProperties[questionId];
    let min = CourseCustomizer.questionsExerciseProperties[min];
    let max = CourseCustomizer.questionsExerciseProperties[max];

    isTime = questionExercise["is_time"];
    if (isTime === "0") isTime = false;
    if (isTime === "1") isTime = true;

    const validationResult = isAnswerValid(answer, isTime,min = min, max = max);
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
    }else{

    }
  }

  function submitAllAnswers() {
    console.log("Submitting all answers");
    console.log(JSON.stringify(CourseCustomizer.validAnswers));
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
          if (data.data && data.data.redirect_url) {
            // If a redirect URL is provided, redirect to it
            setTimeout(() => {
              window.location.href = data.data.redirect_url;
            }, 3000);
          }
          console.log(data.data);
          // If no redirect URL, continue as normal
        } else {
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

  function handleButtonClick(e) {
    if (!e.target.classList.contains("wpProQuiz_button")) return;

    if (
      e.target.value === "Finish Quiz" ||
      e.target.textContent.trim() === "Finish Quiz" ||
      e.target.value === "סיים מבחן" ||
      e.target.textContent.trim() === "סיים מבחן"
    ) {
      submitAllAnswers();
    } else if (
      e.target.value === "back" ||
      e.target.textContent.trim() === "back" ||
      e.target.name === "back"
    ) {
      CourseCustomizer.currentAnswer = null;
    } else {
      CourseCustomizer.currentAnswer = null;
    }
  }

  // Initialize
  function init() {
    disableAllQuizNavigationButtons();
    getExerciseVariables();
    getQuestionsIsTime();
    attachQuestionListeners();
    getQuestionsExerciseProperties();
    document.addEventListener("click", handleButtonClick);


 
  }

  init();
});
