// assets/js/main.js - Interactive features and validations

document.addEventListener('DOMContentLoaded', () => {
    // 1. Terminal Typing Effect for Landing Page
    const terminalElement = document.getElementById('typing-effect');
    if (terminalElement) {
        const words = [
            "Dynamic Programming",
            "Graph Algorithms",
            "Segment Tree & BIT",
            "Game Theory",
            "Number Theory",
            "Greedy Strategies"
        ];
        let wordIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        let typeSpeed = 100;

        function type() {
            const currentWord = words[wordIndex];
            
            if (isDeleting) {
                terminalElement.textContent = currentWord.substring(0, charIndex - 1);
                charIndex--;
                typeSpeed = 50;
            } else {
                terminalElement.textContent = currentWord.substring(0, charIndex + 1);
                charIndex++;
                typeSpeed = 100;
            }

            if (!isDeleting && charIndex === currentWord.length) {
                // Wait at full word
                typeSpeed = 2000;
                isDeleting = true;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                wordIndex = (wordIndex + 1) % words.length;
                typeSpeed = 500;
            }

            setTimeout(type, typeSpeed);
        }

        setTimeout(type, 1000);
    }

    // 2. Client Side Form Validations (Register Form)
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
        });
    }

    // 3. Confirm Dialogs for Admin Panel
    const deleteButtons = document.querySelectorAll('.confirm-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to perform this deletion? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    const statusActions = document.querySelectorAll('.confirm-action');
    statusActions.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const action = btn.textContent.trim().toLowerCase();
            if (!confirm(`Are you sure you want to ${action} this application?`)) {
                e.preventDefault();
            }
        });
    });
});
