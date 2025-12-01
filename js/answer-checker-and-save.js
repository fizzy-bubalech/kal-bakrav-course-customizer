// Initialize CourseCustomizer object to store global state
window.CourseCustomizer = {
  validAnswers: {},
  currentAnswer: null,
  questionsAreRight: {},
  questionsExerciseProperties: {},
  initialized: false,
};

// Ensure DOM is fully loaded before starting
function ensureDOMReady() {
  return new Promise((resolve) => {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", resolve);
    } else {
      resolve();
    }
  });
}

// Validate required dependencies
function validateDependencies() {
  if (typeof myAjax === "undefined" || !myAjax.ajaxurl || !myAjax.nonce) {
    throw new Error("Required myAjax configuration is missing");
  }

  const quizContent = document.querySelector(".wpProQuiz_content");
  if (!quizContent) {
    throw new Error("Quiz content element not found");
  }

  return true;
}

// Disable all quiz navigation buttons
function disableAllQuizNavigationButtons() {
  try {
    const buttons = {
      back: document.querySelectorAll('.wpProQuiz_button[name="back"]'),
      next: document.querySelectorAll('.wpProQuiz_button[name="next"]'),
      checkSingle: document.querySelectorAll(
        '.wpProQuiz_button[name="checkSingle"]',
      ),
    };

    Object.values(buttons).forEach((buttonGroup) => {
      buttonGroup.forEach((button) => {
        button.disabled = true;
      });
    });
  } catch (error) {
    console.error("Error disabling quiz buttons:", error);
    throw error;
  }
}

function add_checkbox() {
  let checkSingleButton = document.querySelector(
    '.wpProQuiz_button[name="checkSingle"]',
  );

  if (!checkSingleButton) return;
  // Create checkbox
  const checkbox = document.createElement("input");
  checkbox.type = "checkbox";
  checkbox.id = "confirmCheckbox";
  checkbox.style.marginRight = "5px"; // Add some space between checkbox and label

  // Create label
  const label = document.createElement("label");
  label.dir = "rtl";
  label.htmlFor = "confirmCheckbox";
  label.textContent = "לא תוכל לבצע הזנת תוצאות נוספת! אתה בטוח בנתונים שהזנת?";

  // Insert them directly before the button
  checkSingleButton.parentNode.insertBefore(label, checkSingleButton);
  checkSingleButton.parentNode.insertBefore(checkbox, label);

  // Add line break between checkbox and button
  const lineBreak = document.createElement("br");
  checkSingleButton.parentNode.insertBefore(lineBreak, checkSingleButton);
  checkSingleButton.style.pointerEvents = "none";
  checkSingleButton.style.cursor = "default";

  // Toggling based on css since the disabled is being used by checker, so both this would have to allow and the checker
  checkbox.addEventListener("change", function () {
    if (this.checked) {
      checkSingleButton.style.pointerEvents = "auto";
      checkSingleButton.style.cursor = "pointer";
    } else {
      checkSingleButton.style.pointerEvents = "none";
      checkSingleButton.style.cursor = "default";
    }
  });
}

// Get quiz ID with validation
function getQuizId() {
  try {
    const quizContent = document.querySelector(".wpProQuiz_content");
    if (!quizContent) {
      throw new Error("Quiz content element not found");
    }

    const quizMetaStr = quizContent.getAttribute("data-quiz-meta");
    if (!quizMetaStr) {
      throw new Error("Quiz meta data not found");
    }

    const quizMeta = JSON.parse(quizMetaStr);
    if (!quizMeta.quiz_pro_id) {
      throw new Error("Quiz ID not found in meta data");
    }

    return quizMeta.quiz_pro_id;
  } catch (error) {
    console.error("Error getting quiz ID:", error);
    throw error;
  }
}

// Load question exercise properties
async function loadQuestionExerciseProperties() {
  try {
    const quizId = getQuizId();

    const response = await fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "get_questions_exercise_properties",
        nonce: myAjax.nonce,
        quiz_id: quizId,
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error || "Failed to load question properties");
    }

    return data.data;
  } catch (error) {
    console.error("Error loading question properties:", error);
    throw error;
  }
}

// Attach event listeners to questions
function attachQuestionListeners() {
  const questionItems = document.querySelectorAll(".wpProQuiz_listItem");
  if (questionItems.length === 0) {
    console.warn("No question items found in DOM");
    return false;
  }

  questionItems.forEach((questionItem) => {
    try {
      const questionMeta = JSON.parse(
        questionItem.getAttribute("data-question-meta"),
      );
      if (!questionMeta || !questionMeta["question_pro_id"]) {
        console.warn("Invalid question metadata", questionItem);
        return;
      }

      const questionId = questionMeta["question_pro_id"];
      CourseCustomizer.questionsAreRight[questionId] = false;

      const inputElement = questionItem.querySelector(
        'input[type="text"], textarea',
      );

      // FIX: Define properties variable and check if it exists before accessing .exercise_type
      const questionProps = CourseCustomizer.questionsExerciseProperties[questionId];

      if(questionProps && questionProps.exercise_type == "TEXT"){
        const radioInputs = questionItem.querySelectorAll(
          'input[type="radio"]'
        );

        if (radioInputs.length > 0) {
          radioInputs.forEach(radioInput => {
            radioInput.addEventListener("change", (e) =>
              handleQuestionKeyStroke(e, questionItem, questionId),
            );
          });
          return;
        }
      } else {
        console.log("ERROR no exercise types");
      }
      if (inputElement) {
        // Initialize the current answer as empty string instead of null
        CourseCustomizer.currentAnswer = "";

        // Add both keyup and input listeners to ensure we catch all changes
        inputElement.addEventListener("keyup", (e) =>
          handleQuestionKeyStroke(e, questionItem, questionId),
        );
        inputElement.addEventListener("input", (e) =>
          handleQuestionKeyStroke(e, questionItem, questionId),
        );
      }
    } catch (error) {
      console.error("Error attaching listener to question:", error);
    }
  });

  return true;
}
// Handle keystrokes in question inputs
function handleQuestionKeyStroke(e, questionItem, questionId) {
  try {
    const button = document.querySelector(
      '.wpProQuiz_QuestionButton:not([style*="display: none"])',
    );

    if (hasAnswerChanged(questionItem)) {
      const validationResult = validateAndStoreAnswer(questionId);

      const inputElement = questionItem.querySelector(
        'input[type="text"], textarea',
      );
      if (inputElement) {
        showAnswerPopup(
          inputElement,
          validationResult === true ? "✓" : validationResult,
          validationResult === true,
        );
      }

      CourseCustomizer.questionsAreRight[questionId] =
        validationResult === true;

      const allQuestionsValid = Object.values(
        CourseCustomizer.questionsAreRight,
      ).every((isRight) => isRight === true);

      toggleProceedButtonVisibility(button, allQuestionsValid);
    }
  } catch (error) {
    console.error("Error handling question keystroke:", error);
  }
}

// Check if answer has changed
function hasAnswerChanged(questionItem) {
  const newAnswer = getAnswerFromQuestionItem(questionItem);
  if (newAnswer !== CourseCustomizer.currentAnswer) {
    CourseCustomizer.currentAnswer = newAnswer;
    return true;
  }
  return false;
}

// Get answer from question item
function getAnswerFromQuestionItem(questionItem) {
  try {
    const questionType = questionItem.dataset.type;
    let answer;

    switch (questionType) {
      case "single":
        const checkedRadio = questionItem.querySelector(
          'input[type="radio"]:checked',
        );
        if (checkedRadio) {
            let label = checkedRadio.closest('label');
            if (!label && checkedRadio.id) {
              label = questionItem.querySelector(`label[for="${checkedRadio.id}"]`);
            }
            answer = label ? label.innerText.trim() : checkedRadio.value;
          } else {
            answer = null;
          }
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
        console.warn("Unknown question type:", questionType);
        answer = null;
    }

    return answer;
  } catch (error) {
    console.error("Error getting answer from question item:", error);
    return null;
  }
}

// Show answer popup
function showAnswerPopup(inputElement, message, isValid) {
  try {
    // Remove existing popup
    const existingPopup = document.getElementById("answerPopup");
    if (existingPopup) {
      existingPopup.remove();
    }

    // Create new popup
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
            background-color: ${isValid ? "#4CAF50" : "#ff6b6b"};
        `;

    const rect = inputElement.getBoundingClientRect();
    popup.style.top = `${rect.bottom + window.scrollY + 5}px`;
    popup.style.left = `${rect.left + window.scrollX}px`;

    document.body.appendChild(popup);

    setTimeout(() => {
      popup.remove();
    }, 3000);
  } catch (error) {
    console.error("Error showing answer popup:", error);
  }
}

// Validate answer
function isAnswerValid(answer, exercise_type, min = 1, max = 9999) {
  try {
    if (exercise_type === "TIME") {
      if (typeof answer !== "string") return "Invalid input type";

      answer = answer.trim();
      const timeRegex = /^(?:(?:([01]?\d|2[0-3]):)?([0-5]?\d):)?([0-5]?\d)$/;
      const matches = answer.match(timeRegex);

      if (!matches) return "X";

      const [_, hours, minutes, seconds] = matches;
      if (!minutes) return "X";

      const time =
        (hours ? parseInt(hours) : 0) * 3600 +
        (minutes ? parseInt(minutes) : 0) * 60 +
        (parseInt(seconds) || 0);

      if (time < min) return "מהר מדי";
      if (time > max) return "לאט מדי";
    } else if (exercise_type === "COUNT") {
      if (typeof answer === "string") {
        answer = answer.trim();
        if (!/^\d+$/.test(answer)) return "X";
      }

      const numAnswer = Number(answer);
      if (isNaN(numAnswer) || !Number.isInteger(numAnswer)) return "X";
      if (numAnswer < min || numAnswer > max) return "בטוח? תבדוק שוב";
    } else {

      if (typeof answer === "string") {
        console.log("detected a valid text answer")
        answer = answer.trim();
        if (answer.length > max || answer.length < min) return "תשובה ארוכה מדי";
      }
    }

    return true;
  } 
  catch (error) {
    console.error("Error validating answer:", error);
    return "X";
  }
}


// Validate and store answer
function validateAndStoreAnswer(questionId) {
  try {
    const answer = CourseCustomizer.currentAnswer;
    const questionExercise =
      CourseCustomizer.questionsExerciseProperties[questionId];

    if (!questionExercise) {
      console.error(
        "Question exercise properties not found for ID:",
        questionId,
      );
      return "X";
    }

    if (
      questionExercise.min === null ||
      questionExercise.max === null ||
      questionExercise.exercise_type === null
    ) {
      return true;
    }
    const min = parseInt(questionExercise.min);
    const max = parseInt(questionExercise.max);
    const exerciseType = questionExercise.exercise_type;
    if(!exerciseType) console.log("ERROR");
    const validationResult = isAnswerValid(answer, exerciseType, min, max);
    if (validationResult === true) {
      storeValidAnswer(answer, questionId);
    }

    return validationResult;
  } catch (error) {
    console.error("Error validating and storing answer:", error);
    return "X";
  }
}

// Store valid answer
function storeValidAnswer(answer, questionId) {
  CourseCustomizer.validAnswers[questionId] = answer;
  console.log(CourseCustomizer.validAnswers);
}

// Toggle proceed button visibility
function toggleProceedButtonVisibility(button, isValid) {
  let confirmCheckBox = document.getElementById("confirmCheckbox");
  if (button) {
    button.disabled = !isValid;
  }
}

// Submit all answers
async function submitAllAnswers() {
  try {
    console.log(
      "Submitting answers:",
      JSON.stringify(CourseCustomizer.validAnswers),
    );

    const response = await fetch(myAjax.ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "save_quiz_results",
        quizData: JSON.stringify(CourseCustomizer.validAnswers),
        nonce: myAjax.nonce,
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success) {
      throw new Error(JSON.stringify(data) + "Failed to save quiz results");
    }

    if (data.data?.redirect_url) {
      setTimeout(() => {
        window.location.href = data.data.redirect_url;
      }, 3000);
    }

    return true;
  } catch (error) {
    console.error("Error submitting answers:", error);
    showPopupErrorAndReload();
    throw error;
  }
}

// Show error popup and reload
function showPopupErrorAndReload() {
  alert("There was an error. Please try again.");
  location.reload();
}

// Handle button clicks
function handleButtonClick(e) {
  try {
    if (!e.target.classList.contains("wpProQuiz_button")) return;

    const buttonText = e.target.value || e.target.textContent.trim();
    const isFinishButton = ["Finish Quiz", "סיים מבחן"].includes(buttonText);
    const isBackButton = buttonText === "back" || e.target.name === "back";

    if (isFinishButton) {
      submitAllAnswers();
    } else {
      CourseCustomizer.currentAnswer = null;
    }
  } catch (error) {
    console.error("Error handling button click:", error);
  }
}

// Main initialization function
async function init() {
  try {
    // Validate dependencies first
    validateDependencies();

    // Disable navigation buttons
    disableAllQuizNavigationButtons();

    // Load all required data concurrently
    const [questionProperties] = await Promise.all([
      loadQuestionExerciseProperties(),
    ]);

    // Set the loaded data to CourseCustomizer
    CourseCustomizer.questionsExerciseProperties = questionProperties;
    console.log(CourseCustomizer.questionsExerciseProperties);

    // Attach listeners only after data is loaded
    attachQuestionListeners();

    //Add the submit checkbox
    add_checkbox();

    // Add click handler
    document.addEventListener("click", handleButtonClick);

    // Mark as initialized
    CourseCustomizer.initialized = true;

    console.log("Quiz initialization completed successfully");
  } catch (error) {
    console.error("Failed to initialize quiz:", error);
    throw error;
  }
}

// Start the application with proper error handling
async function startApplication() {
  try {
    // Ensure DOM is ready before starting
    await ensureDOMReady();

    // Initialize the application
    await init();

    console.log("Application started successfully");
  } catch (error) {
    console.error("Failed to start application:", error);
  }
}

// Start the application when the script loads
startApplication();
