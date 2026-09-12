import fs from 'fs';

const API_BASE = 'http://localhost:8000/api';
let testResults = [];

function logResult(category, name, status, details = '') {
    const symbol = status ? '✅ PASS' : '❌ FAIL';
    console.log(`${symbol} | ${category}: ${name}`);
    if (!status && details) console.error(`   Details:`, details);
    testResults.push({ category, name, status, details });
}

async function runTests() {
    console.log("--- STARTING LOCAL API TESTS ---");
    let token = null;
    let refreshToken = null;

    try {
        // --- AUTH & SECURITY ---
        // Login
        let res = await fetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: 'demo@edupath.id', password: 'demo123' })
        });
        let data = await res.json();
        if (data.token) {
            token = data.token;
            refreshToken = data.refresh_token;
            logResult('AUTH', 'login', true);
        } else {
            logResult('AUTH', 'login', false, data);
        }

        // Me
        res = await fetch(`${API_BASE}/auth.php?action=me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        data = await res.json();
        logResult('AUTH', 'me', !!data?.user?.id, data);

        // Security: Invalid JWT
        res = await fetch(`${API_BASE}/auth.php?action=me`, {
            headers: { 'Authorization': `Bearer invalid_token_123` }
        });
        logResult('SECURITY', 'malformed JWT rejected', res.status === 401);

        // Refresh Token
        res = await fetch(`${API_BASE}/auth.php?action=refresh`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: refreshToken })
        });
        let refreshData = await res.json();
        let newToken = refreshData.token;
        let newRefreshToken = refreshData.refresh_token;
        logResult('AUTH', 'refresh', !!newToken && newRefreshToken !== refreshToken, refreshData);

        // Security: Reuse old refresh token
        res = await fetch(`${API_BASE}/auth.php?action=refresh`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: refreshToken })
        });
        logResult('SECURITY', 'reuse old refresh token rejected', res.status >= 400);
        
        token = newToken; // Update active token
        refreshToken = newRefreshToken;

        // Logout
        res = await fetch(`${API_BASE}/auth.php?action=logout`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}` 
            },
            body: JSON.stringify({ refresh_token: refreshToken })
        });
        logResult('AUTH', 'logout', res.status === 200);

        // Relogin for further tests
        res = await fetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: 'demo@edupath.id', password: 'demo123' })
        });
        data = await res.json();
        token = data.token;

        // --- SPP ---
        res = await fetch(`${API_BASE}/spp.php?action=data`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        data = await res.json();
        let isSppValid = data.universities && data.programs && Array.isArray(data.universities);
        let hasNoAnswerKey = JSON.stringify(data).indexOf('answer_key') === -1;
        logResult('SPP', 'data hierarchy', isSppValid);
        logResult('SPP', 'no answer key exposed', hasNoAnswerKey);

        // --- MATERIALS ---
        res = await fetch(`${API_BASE}/materials.php?action=list`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        data = await res.json();
        logResult('MATERIALS', 'list', Array.isArray(data));

        // --- QUIZ & TRYOUT ---
        // Backward compatibility check
        res = await fetch(`${API_BASE}/quiz.php?action=questions&subtes=TPS`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        data = await res.json();
        logResult('QUIZ', 'questions (backward compat)', Array.isArray(data));
        
        // Start attempt (Mobile Flow)
        res = await fetch(`${API_BASE}/quiz.php?action=start`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ limit: 5 })
        });
        data = await res.json();
        let attempt_id = data.attempt_id;
        logResult('QUIZ', 'start attempt', !!attempt_id && Array.isArray(data.questions));
        
        // Submit attempt
        if (data.questions && data.questions.length > 0) {
            res = await fetch(`${API_BASE}/quiz.php?action=submit`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    attempt_id: attempt_id,
                    answers: [ { question_id: data.questions[0].id, answer: 'A' } ]
                })
            });
            let submitData = await res.json();
            logResult('QUIZ', 'submit attempt', submitData.success === true && !!submitData.result_id);

            // Submit attempt twice (Idempotency)
            res = await fetch(`${API_BASE}/quiz.php?action=submit`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    attempt_id: attempt_id,
                    answers: [ { question_id: data.questions[0].id, answer: 'A' } ]
                })
            });
            let duplicateData = await res.json();
            logResult('QUIZ', 'duplicate submit (idempotent)', duplicateData.success === true && duplicateData.result_id === submitData.result_id);
        } else {
            logResult('QUIZ', 'submit attempt', false, 'No questions returned to test submission');
        }

        // --- SUMMARY REPORT ---
        console.log("\n--- JSON REPORT ---");
        fs.writeFileSync('test_results.json', JSON.stringify(testResults, null, 2));
        console.log("Results saved to test_results.json");

    } catch (e) {
        console.error("Critical Test Failure:", e);
    }
}

runTests();
