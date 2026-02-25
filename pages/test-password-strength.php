<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Strength Test</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .test-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn {
            background: #13084A;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #1a0c5e;
        }
        .test-results {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        h1 {
            color: #13084A;
            text-align: center;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info h3 {
            margin-top: 0;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <h1>🔒 Тест за Сила на Паролата</h1>
    
    <div class="info">
        <h3>Тествайте валидацията на паролата</h3>
        <p>Тази страница е създадена за тестване на функционалността за проверка на силата на паролата.</p>
        <p><strong>Изисквания за паролата:</strong></p>
        <ul>
            <li>Минимум 8 символа</li>
            <li>Поне една главна буква (A-Z)</li>
            <li>Поне една малка буква (a-z)</li>
            <li>Поне една цифра (0-9)</li>
            <li>Поне един специален символ (!@#$%^&*)</li>
        </ul>
    </div>

    <div class="test-form">
        <form id="testForm">
            <div class="form-group">
                <label for="test_password">Тествайте парола:</label>
                <input type="password" id="test_password" name="test_password" required>
            </div>
            
            <div class="form-group">
                <label for="test_confirm_password">Потвърдете паролата:</label>
                <input type="password" id="test_confirm_password" name="test_confirm_password" required>
            </div>
            
            <button type="submit" class="btn">Провери Паролата</button>
        </form>
        
        <div id="testResults" class="test-results" style="display: none;">
            <h3>Резултат:</h3>
            <p id="resultMessage"></p>
        </div>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 4px;">
        <h3>Примери за тестване:</h3>
        <ul>
            <li><strong>Слаба:</strong> <code>test</code> (твърде кратка, липсват изисквания)</li>
            <li><strong>Средна:</strong> <code>Test1234</code> (липсва специален символ)</li>
            <li><strong>Добра:</strong> <code>Test123!</code> (отговаря на 80% от изискванията)</li>
            <li><strong>Силна:</strong> <code>Test1234!</code> (отговаря на всички изисквания)</li>
            <li><strong>Много силна:</strong> <code>MyP@ssw0rd2026!</code> (дълга и сложна)</li>
        </ul>
    </div>

    <script src="/assets/js/password-strength.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize password strength checker
            const passwordChecker = new PasswordStrengthChecker('test_password', 'test_confirm_password', {
                minLength: 8,
                requireUppercase: true,
                requireLowercase: true,
                requireNumbers: true,
                requireSpecialChars: true
            });
            
            // Handle form submission
            const form = document.getElementById('testForm');
            const resultsDiv = document.getElementById('testResults');
            const resultMessage = document.getElementById('resultMessage');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                resultsDiv.style.display = 'block';
                
                if (passwordChecker.isValid()) {
                    resultMessage.innerHTML = '<span style="color: #4caf50; font-weight: bold;">✓ Успех!</span> Паролата отговаря на всички изисквания за сигурност.';
                    resultMessage.style.background = '#e8f5e9';
                    resultMessage.style.padding = '10px';
                    resultMessage.style.borderRadius = '4px';
                } else {
                    resultMessage.innerHTML = '<span style="color: #f44336; font-weight: bold;">✗ Грешка!</span> Паролата не отговаря на изискванията за сигурност или паролите не съвпадат.';
                    resultMessage.style.background = '#ffebee';
                    resultMessage.style.padding = '10px';
                    resultMessage.style.borderRadius = '4px';
                }
            });
        });
    </script>
</body>
</html>

