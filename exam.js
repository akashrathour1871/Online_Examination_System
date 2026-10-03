let questions = [];
let currentQuestion = 0;
let answers = {};
let timeLeft = 600;
let timerInterval = null;
let submitted = false;

// Exam name URL se lo
const urlParams = new URLSearchParams(window.location.search);
let examName = urlParams.get("exam_name");

// Agar URL se nahi mila to default exam
if (!examName) {
    examName = "C Programming Test";
}

const questionElement = document.getElementById("question");
const optionsContainer = document.querySelector(".options");

async function loadQuestions() {
    try {
        const response = await fetch(
            "backend/get_questions.php?exam_name=" +
            encodeURIComponent(examName)
        );

        const data = await response.json();

        if (!response.ok || !data.success) {
            alert(data.message || "Questions load nahi hue.");
            return;
        }

        questions = data.questions || [];

        if (questions.length === 0) {
            alert("Is exam ke questions database mein nahi hain.");
            return;
        }

        // Exam title
        const examTitle = document.getElementById("examTitle");
        if (examTitle) {
            examTitle.textContent = examName;
        }

        // Total questions
        const totalQuestions = document.getElementById("totalQuestions");
        if (totalQuestions) {
            totalQuestions.textContent = questions.length;
        }

        const remainingCount =
            document.getElementById("remainingCount");

        if (remainingCount) {
            remainingCount.textContent = questions.length;
        }

        createQuestionNumbers();
        loadQuestion();
        startTimer();

    } catch (error) {
        console.error(error);
        alert("Server se questions nahi mile.");
    }
}

function createQuestionNumbers() {
    const container =
        document.getElementById("questionNumbers");

    if (!container) return;

    container.innerHTML = "";

    questions.forEach((q, index) => {

        const button = document.createElement("button");

        button.type = "button";
        button.textContent = index + 1;
        button.className = "question-number";

        button.addEventListener("click", function () {
            currentQuestion = index;
            loadQuestion();
        });

        container.appendChild(button);
    });
}

function loadQuestion() {

    if (!questions.length) return;

    const q = questions[currentQuestion];

    document.getElementById("questionNumber").textContent =
        currentQuestion + 1;

    questionElement.textContent = q.question;

    optionsContainer.innerHTML = "";

    const optionKeys = ["a", "b", "c", "d"];

    optionKeys.forEach(function (key) {

        const optionText = q["option_" + key];

        if (optionText === undefined || optionText === null) {
            return;
        }

        const label = document.createElement("label");
        label.className = "option";

        const input = document.createElement("input");

        input.type = "radio";
        input.name = "answer";
        input.value = key.toUpperCase();

        const span = document.createElement("span");

        span.textContent = optionText;

        const questionId = String(q.id);

        if (answers[questionId] === key.toUpperCase()) {
            input.checked = true;
        }

        input.addEventListener("change", function () {

            answers[questionId] =
                key.toUpperCase();

            updateQuestionNumbers();
        });

        label.appendChild(input);
        label.appendChild(span);

        optionsContainer.appendChild(label);
    });

    document.getElementById("previousBtn").disabled =
        currentQuestion === 0;

    document.getElementById("nextBtn").style.display =
        currentQuestion === questions.length - 1
            ? "none"
            : "inline-block";

    const submitBtn =
        document.getElementById("submitBtn");

    submitBtn.style.display = "inline-block";
    submitBtn.disabled = submitted;

    updateQuestionNumbers();
}

function updateQuestionNumbers() {

    const buttons =
        document.querySelectorAll(
            "#questionNumbers button"
        );

    let attempted = 0;

    buttons.forEach(function (button, index) {

        button.classList.remove(
            "current",
            "answered"
        );

        const q = questions[index];

        if (q && answers[String(q.id)]) {

            button.classList.add("answered");

            attempted++;
        }

        if (index === currentQuestion) {

            button.classList.add("current");
        }
    });

    const attemptedElement =
        document.getElementById("attemptedCount");

    const remainingElement =
        document.getElementById("remainingCount");

    if (attemptedElement) {
        attemptedElement.textContent = attempted;
    }

    if (remainingElement) {
        remainingElement.textContent =
            questions.length - attempted;
    }
}

function nextQuestion() {

    if (currentQuestion <
        questions.length - 1) {

        currentQuestion++;

        loadQuestion();
    }
}

function previousQuestion() {

    if (currentQuestion > 0) {

        currentQuestion--;

        loadQuestion();
    }
}

function startTimer() {

    const timer =
        document.getElementById("timer");

    function updateTimer() {

        const minutes =
            Math.floor(timeLeft / 60);

        const seconds =
            timeLeft % 60;

        timer.textContent =
            String(minutes).padStart(2, "0") +
            ":" +
            String(seconds).padStart(2, "0");
    }

    updateTimer();

    timerInterval = setInterval(function () {

        timeLeft--;

        updateTimer();

        if (timeLeft <= 0) {

            clearInterval(timerInterval);

            alert(
                "Time is over! Exam submitting now."
            );

            submitExam(true);
        }

    }, 1000);
}

async function submitExam(timeUp = false) {

    if (submitted) return;

    if (!timeUp) {

        const confirmSubmit = confirm(
            "Kya aap exam submit karna chahte hain?"
        );

        if (!confirmSubmit) return;
    }

    submitted = true;

    const submitBtn =
        document.getElementById("submitBtn");

    submitBtn.disabled = true;

    submitBtn.textContent = "Submitting...";

    if (timerInterval) {
        clearInterval(timerInterval);
    }

    try {

        const response = await fetch(
            "backend/submit_exam.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({

                    exam_name: examName,

                    answers: answers
                })
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Result save nahi hua."
            );
        }

        const examResult = {

            exam_name: examName,

            score: data.score,

            total: data.total,

            percentage: data.percentage,

            date: new Date().toLocaleString()
        };

        localStorage.setItem(
            "examResult",
            JSON.stringify(examResult)
        );

        window.location.href =
            "result.html";

    } catch (error) {

        console.error(error);

        alert(
            error.message ||
            "Server error. Dobara try karo."
        );

        submitted = false;

        submitBtn.disabled = false;

        submitBtn.textContent =
            "Submit Exam";
    }
}

loadQuestions();