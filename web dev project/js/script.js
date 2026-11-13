

document.addEventListener('DOMContentLoaded', function () {


    // 1. Mobile Navigation Toggle

    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', function () {
            navMenu.classList.toggle('open');
        });
    }


    // 2. Delete Confirmation Dialog

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.btn-delete, .confirm-action');
        if (button) {
            const confirmMessage = button.getAttribute('data-confirm') ||
                'Are you sure you want to delete this record? This action cannot be undone.';
            const userConfirmed = confirm(confirmMessage);
            if (!userConfirmed) {
                event.preventDefault();
            }
        }
    });


    // 3. Client-Side Form Validation Helper

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (form.matches('form[data-validate]')) {
            let isValid = true;
            const requiredInputs = form.querySelectorAll('[required]');

            requiredInputs.forEach(function (input) {
                if (!input.value.trim()) {
                    isValid = false;
                    input.style.borderColor = '#dc2626';
                } else {
                    input.style.borderColor = '#cbd5e1';
                }
            });

            if (!isValid) {
                event.preventDefault();
                alert('Please fill out all required fields before submitting.');
            }
        }
    });


    // 4. Alert Banner & URL Flash Parameter Handling

    handleUrlAlerts();
    initAlertDismissal();


    // 5. Page-Specific Initializers

    if (document.getElementById('public-notices-list')) {
        initPublicHomepage();
    }

    if (document.getElementById('login-card')) {
        initLoginPage();
    }

    if (document.getElementById('students-table-body')) {
        initManageStudents();
    }

    if (document.getElementById('faculty-table-body')) {
        initManageFaculty();
    }

    if (document.getElementById('principal-notices-table-body')) {
        initPrincipalNotices();
    }

    if (document.getElementById('attendance-sheet-body')) {
        initAttendanceSheet();
    }

    if (document.getElementById('assignments-table-body')) {
        initAssignments();
    }

    if (document.getElementById('marks-table-body')) {
        initMarks();
    }

    if (document.getElementById('faculty-notices-container')) {
        initFacultyNotices();
    }
});


// Utility Functions

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr + 'T00:00:00');
    if (isNaN(date.getTime())) return escapeHtml(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

/**
 * Display a flash alert banner at the top of the container
 */
function showAlert(type, message) {
    const container = document.getElementById('alert-container') || document.querySelector('.main-content');
    if (!container) return;

    const alertClass = (type === 'success') ? 'alert-success' : 'alert-error';
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass}`;
    alertDiv.textContent = message;

    if (container.firstChild) {
        container.insertBefore(alertDiv, container.firstChild);
    } else {
        container.appendChild(alertDiv);
    }

    setTimeout(function () {
        alertDiv.style.transition = 'opacity 0.6s ease';
        alertDiv.style.opacity = '0';
        setTimeout(function () {
            if (alertDiv.parentNode) alertDiv.parentNode.removeChild(alertDiv);
        }, 600);
    }, 5000);
}

/**
 * Reads ?success=... or ?error=... or ?logout=success from URL query params
 */
function handleUrlAlerts() {
    const params = new URLSearchParams(window.location.search);

    if (params.has('logout')) {
        showAlert('success', 'You have been successfully logged out.');
    }
    if (params.has('success')) {
        showAlert('success', params.get('success'));
    }
    if (params.has('error')) {
        showAlert('error', params.get('error'));
    }
}

/**
 * Automatically dismisses existing static alert banners after 5 seconds
 */
function initAlertDismissal() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alertBox) {
        setTimeout(function () {
            alertBox.style.transition = 'opacity 0.6s ease';
            alertBox.style.opacity = '0';
            setTimeout(function () {
                if (alertBox.parentNode) alertBox.parentNode.removeChild(alertBox);
            }, 600);
        }, 5000);
    });
}


// Page 1: Public School Homepage (index.html)

function initPublicHomepage() {
    const noticeContainer = document.getElementById('public-notices-list');

    // 1. Fetch live notices from backend
    if (noticeContainer) {
        fetch('backend/notices.php?action=public')
            .then(res => res.json())
            .then(res => {
                if (res.success && res.data && res.data.length > 0) {
                    noticeContainer.innerHTML = res.data.map(n => `
                        <div class="notice-item">
                            <div class="notice-header">
                                <span class="notice-title">${escapeHtml(n.title)}</span>
                                <span class="notice-date">Published: ${formatDate(n.posted_date)}</span>
                            </div>
                            <div class="notice-content">
                                ${escapeHtml(n.content).replace(/\n/g, '<br>')}
                            </div>
                            <div class="notice-author">
                                Posted by: ${escapeHtml(n.posted_by)}
                            </div>
                        </div>
                    `).join('');
                } else {
                    noticeContainer.innerHTML = '<p style="color: var(--text-muted); padding: 15px 0;">No notices published at this moment.</p>';
                }
            })
            .catch(err => {
                console.error('Error fetching public notices:', err);
                noticeContainer.innerHTML = '<p style="color: var(--text-muted); padding: 15px 0;">Unable to load notices right now.</p>';
            });
    }

    // 2. Check if visitor is logged in and adjust navigation buttons
    fetch('backend/check_auth.php')
        .then(res => res.json())
        .then(res => {
            if (res.logged_in && res.user) {
                const role = res.user.role;
                const dashLink = `${role}/dashboard.php`;

                // Update hero CTA button if present
                const heroPortalBtn = document.getElementById('hero-portal-btn');
                if (heroPortalBtn) {
                    heroPortalBtn.href = dashLink;
                    heroPortalBtn.textContent = 'Go to My Dashboard →';
                }

                // Update nav menu item
                const navLoginItem = document.getElementById('nav-login-item');
                if (navLoginItem) {
                    navLoginItem.innerHTML = `
                        <a href="${dashLink}" class="nav-link nav-link-login">My Dashboard</a>
                    `;
                }

                // Add logout option to nav menu
                const navMenu = document.querySelector('.nav-menu');
                if (navMenu && !document.getElementById('nav-logout-item')) {
                    const logoutLi = document.createElement('li');
                    logoutLi.id = 'nav-logout-item';
                    logoutLi.innerHTML = `<a href="backend/logout.php" class="nav-link">Logout</a>`;
                    navMenu.appendChild(logoutLi);
                }
            }
        })
        .catch(err => {
            // Unauthenticated visitor is default
        });
}


// Page 2: Portal Login (login.html)

function initLoginPage() {
    // If user is already logged in, redirect directly to their dashboard
    fetch('backend/check_auth.php')
        .then(res => res.json())
        .then(res => {
            if (res.logged_in && res.user) {
                window.location.href = `${res.user.role}/dashboard.php`;
            }
        })
        .catch(() => { });
}


// Page 3: Manage Students (principal/students.html)

function initManageStudents() {
    const tableBody = document.getElementById('students-table-body');
    const form = document.getElementById('student-form');
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const studentId = urlParams.get('id');

    // 1. If in Edit Mode, fetch existing student details to fill form
    if (action === 'edit' && studentId) {
        fetch(`../backend/students.php?action=get&id=${studentId}`)
            .then(res => {
                if (res.status === 401) {
                    window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Principal.');
                    return;
                }
                return res.json();
            })
            .then(res => {
                if (res && res.success && res.data) {
                    const s = res.data;
                    document.getElementById('student_id').value = s.id;
                    document.getElementById('full_name').value = s.full_name || '';
                    document.getElementById('roll_number').value = s.roll_number || '';
                    document.getElementById('class_name').value = s.class_name || '';
                    document.getElementById('gender').value = s.gender || 'Male';
                    document.getElementById('dob').value = s.dob || '';
                    document.getElementById('phone').value = s.phone || '';
                    document.getElementById('email').value = s.email || '';
                    document.getElementById('username').value = s.username || '';
                    document.getElementById('parent_name').value = s.parent_name || '';
                    document.getElementById('parent_phone').value = s.parent_phone || '';
                    document.getElementById('address').value = s.address || '';

                    // Change UI mode to Edit
                    document.getElementById('form-heading').textContent = `Edit Student: ${s.full_name}`;
                    document.getElementById('submit-btn').textContent = 'Update Student Profile';
                    const cancelBtn = document.getElementById('cancel-edit-btn');
                    if (cancelBtn) cancelBtn.style.display = 'inline-block';

                    const passHelp = document.getElementById('password-help');
                    if (passHelp) {
                        passHelp.textContent = 'Leave password blank to keep current password.';
                    }
                }
            })
            .catch(err => console.error('Error fetching student record:', err));
    }

    // 2. Fetch and render list of all enrolled students
    fetch('../backend/students.php?action=list')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Principal.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                if (res.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 25px;">No students enrolled yet. Add your first student using the form above.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = res.data.map(s => `
                    <tr>
                        <td><strong>${escapeHtml(s.roll_number)}</strong></td>
                        <td>
                            <strong>${escapeHtml(s.full_name)}</strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(s.gender)}, DOB: ${formatDate(s.dob)}</div>
                        </td>
                        <td><span class="role-badge" style="background:#e0f2fe; color:#0369a1;">${escapeHtml(s.class_name)}</span></td>
                        <td>
                            <div>${escapeHtml(s.phone || 'N/A')}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(s.email)}</div>
                        </td>
                        <td>
                            <div>${escapeHtml(s.parent_name || 'N/A')}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(s.parent_phone || '')}</div>
                        </td>
                        <td><code>${escapeHtml(s.username)}</code></td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a href="students.html?action=edit&id=${s.id}" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="../backend/students.php?action=delete&id=${s.id}" 
                                   class="btn btn-sm btn-danger btn-delete" 
                                   data-confirm="Are you sure you want to delete ${escapeHtml(s.full_name)}? All their attendance and marks will also be deleted.">Delete</a>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(err => {
            console.error('Error fetching students:', err);
            tableBody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: #dc2626; padding: 20px;">Error loading students data.</td></tr>`;
        });
}


// Page 4: Manage Faculty (principal/faculty.html)

function initManageFaculty() {
    const tableBody = document.getElementById('faculty-table-body');
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const facultyId = urlParams.get('id');

    // 1. If in Edit Mode, fetch faculty member details
    if (action === 'edit' && facultyId) {
        fetch(`../backend/faculty.php?action=get&id=${facultyId}`)
            .then(res => {
                if (res.status === 401) {
                    window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Principal.');
                    return;
                }
                return res.json();
            })
            .then(res => {
                if (res && res.success && res.data) {
                    const f = res.data;
                    document.getElementById('faculty_id').value = f.id;
                    document.getElementById('full_name').value = f.full_name || '';
                    document.getElementById('employee_id').value = f.employee_id || '';
                    document.getElementById('subject_specialization').value = f.subject_specialization || '';
                    document.getElementById('qualification').value = f.qualification || '';
                    document.getElementById('phone').value = f.phone || '';
                    document.getElementById('email').value = f.email || '';
                    document.getElementById('username').value = f.username || '';
                    document.getElementById('address').value = f.address || '';

                    document.getElementById('form-heading').textContent = `Edit Faculty: ${f.full_name}`;
                    document.getElementById('submit-btn').textContent = 'Update Faculty Profile';
                    const cancelBtn = document.getElementById('cancel-edit-btn');
                    if (cancelBtn) cancelBtn.style.display = 'inline-block';

                    const passHelp = document.getElementById('password-help');
                    if (passHelp) {
                        passHelp.textContent = 'Leave password blank to keep current password.';
                    }
                }
            })
            .catch(err => console.error('Error fetching faculty record:', err));
    }

    // 2. Fetch and render list of all faculty members
    fetch('../backend/faculty.php?action=list')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Principal.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                if (res.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 25px;">No faculty members registered yet.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = res.data.map(f => `
                    <tr>
                        <td><strong>${escapeHtml(f.employee_id)}</strong></td>
                        <td>
                            <strong>${escapeHtml(f.full_name)}</strong>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(f.qualification || 'Teaching Staff')}</div>
                        </td>
                        <td><span class="role-badge" style="background:#fef3c7; color:#92400e;">${escapeHtml(f.subject_specialization)}</span></td>
                        <td>
                            <div>${escapeHtml(f.phone || 'N/A')}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(f.email)}</div>
                        </td>
                        <td><code>${escapeHtml(f.username)}</code></td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <a href="faculty.html?action=edit&id=${f.id}" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="../backend/faculty.php?action=delete&id=${f.id}" 
                                   class="btn btn-sm btn-danger btn-delete" 
                                   data-confirm="Are you sure you want to delete ${escapeHtml(f.full_name)}? Their assignments will also be deleted.">Delete</a>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(err => {
            console.error('Error loading faculty list:', err);
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #dc2626; padding: 20px;">Error loading faculty records.</td></tr>`;
        });
}


// Page 5: Manage Notices (principal/notices.html)

function initPrincipalNotices() {
    const tableBody = document.getElementById('principal-notices-table-body');

    // Fetch and render notices
    fetch('../backend/notices.php?action=list')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Principal.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                if (res.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 25px;">No school notices published yet.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = res.data.map(n => `
                    <tr>
                        <td><strong>${formatDate(n.posted_date)}</strong></td>
                        <td><strong style="color: var(--primary-blue);">${escapeHtml(n.title)}</strong></td>
                        <td>
                            <div style="max-height: 80px; overflow-y: auto; font-size: 0.85rem; line-height: 1.4;">
                                ${escapeHtml(n.content).replace(/\n/g, '<br>')}
                            </div>
                        </td>
                        <td>${escapeHtml(n.posted_by)}</td>
                        <td>
                            <a href="../backend/notices.php?action=delete&id=${n.id}" 
                               class="btn btn-sm btn-danger btn-delete" 
                               data-confirm="Are you sure you want to delete this notice?">Delete</a>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(err => {
            console.error('Error fetching notices:', err);
            tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #dc2626; padding: 20px;">Error loading notices.</td></tr>`;
        });
}


// Page 6: Student Attendance (faculty/attendance.html)

function initAttendanceSheet() {
    const classSelect = document.getElementById('class-select');
    const dateInput = document.getElementById('date-select');
    const loadBtn = document.getElementById('load-sheet-btn');
    const tableBody = document.getElementById('attendance-sheet-body');
    const hiddenDate = document.getElementById('attendance_date');
    const hiddenClass = document.getElementById('class_name');

    const urlParams = new URLSearchParams(window.location.search);
    const initialDate = urlParams.get('date') || new Date().toISOString().split('T')[0];
    const initialClass = urlParams.get('class') || '';

    dateInput.value = initialDate;

    // 1. Fetch available classes for dropdown
    fetch('../backend/attendance.php?action=get_classes')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Faculty.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                classSelect.innerHTML = res.data.map(c => `
                    <option value="${escapeHtml(c)}" ${c === initialClass ? 'selected' : ''}>${escapeHtml(c)}</option>
                `).join('');

                const selectedClass = classSelect.value;
                if (selectedClass) {
                    loadSheet(selectedClass, dateInput.value);
                }
            }
        })
        .catch(err => console.error('Error fetching classes:', err));

    // 2. Load attendance sheet for selected class & date
    function loadSheet(className, dateVal) {
        if (!className || !dateVal) return;

        hiddenDate.value = dateVal;
        hiddenClass.value = className;

        tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: var(--text-muted);">Loading student attendance sheet...</td></tr>`;

        fetch(`../backend/attendance.php?action=get_sheet&class=${encodeURIComponent(className)}&date=${encodeURIComponent(dateVal)}`)
            .then(res => res.json())
            .then(res => {
                if (res && res.success && res.data && res.data.students) {
                    const students = res.data.students;

                    if (students.length === 0) {
                        tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 25px;">No students enrolled in ${escapeHtml(className)}.</td></tr>`;
                        return;
                    }

                    tableBody.innerHTML = students.map((s, idx) => {
                        // Default to Present if not yet marked
                        const isPresent = (s.status === null || s.status === undefined || s.status === 'Present');
                        const isAbsent = (s.status === 'Absent');
                        const remarkVal = s.remarks || '';

                        return `
                            <tr>
                                <td>${idx + 1}</td>
                                <td><strong>${escapeHtml(s.roll_number)}</strong></td>
                                <td>${escapeHtml(s.full_name)}</td>
                                <td>
                                    <div class="attendance-radio-group">
                                        <label class="radio-label">
                                            <input type="radio" name="attendance[${s.id}]" value="Present" ${isPresent ? 'checked' : ''}> 
                                            <span style="color: #15803d; font-weight: 600;">Present</span>
                                        </label>
                                        <label class="radio-label" style="margin-left: 15px;">
                                            <input type="radio" name="attendance[${s.id}]" value="Absent" ${isAbsent ? 'checked' : ''}> 
                                            <span style="color: #b91c1c; font-weight: 600;">Absent</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="remarks[${s.id}]" 
                                           class="form-control" 
                                           placeholder="e.g. On time, Sick leave" 
                                           value="${escapeHtml(remarkVal)}" 
                                           style="padding: 6px 10px; font-size: 0.85rem;">
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            })
            .catch(err => {
                console.error('Error loading attendance sheet:', err);
                tableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #dc2626; padding: 20px;">Failed to load attendance records.</td></tr>`;
            });
    }

    if (loadBtn) {
        loadBtn.addEventListener('click', function () {
            loadSheet(classSelect.value, dateInput.value);
        });
    }
}


// Page 7: Assignments (faculty/assignments.html)

function initAssignments() {
    const classSelect = document.getElementById('class_name');
    const tableBody = document.getElementById('assignments-table-body');

    // 1. Populate Classes in Form Dropdown
    if (classSelect) {
        fetch('../backend/assignments.php?action=get_classes')
            .then(res => {
                if (res.status === 401) {
                    window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Faculty.');
                    return;
                }
                return res.json();
            })
            .then(res => {
                if (res && res.success && res.data) {
                    classSelect.innerHTML = res.data.map(c => `
                        <option value="${escapeHtml(c)}">${escapeHtml(c)}</option>
                    `).join('');
                }
            })
            .catch(err => console.error('Error fetching classes:', err));
    }

    // 2. Fetch and render assignments created by this teacher
    fetch('../backend/assignments.php?action=list')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Faculty.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                if (res.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 25px;">You have not published any assignments yet. Use the form above to post your first assignment.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = res.data.map(a => `
                    <tr>
                        <td><strong>${formatDate(a.due_date)}</strong></td>
                        <td><span class="role-badge" style="background:#e0f2fe; color:#0369a1;">${escapeHtml(a.class_name)}</span></td>
                        <td>${escapeHtml(a.subject)}</td>
                        <td><strong style="color: var(--primary-blue);">${escapeHtml(a.title)}</strong></td>
                        <td>
                            <div style="max-height: 80px; overflow-y: auto; font-size: 0.85rem; line-height: 1.4;">
                                ${escapeHtml(a.description).replace(/\n/g, '<br>')}
                            </div>
                        </td>
                        <td>
                            <a href="../backend/assignments.php?action=delete&id=${a.id}" 
                               class="btn btn-sm btn-danger btn-delete" 
                               data-confirm="Are you sure you want to delete assignment '${escapeHtml(a.title)}'?">Delete</a>
                        </td>
                    </tr>
                `).join('');
            }
        })
        .catch(err => {
            console.error('Error fetching assignments:', err);
            tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #dc2626; padding: 20px;">Error loading assignments.</td></tr>`;
        });
}


// Page 8: Student Marks (faculty/marks.html)

function initMarks() {
    const studentSelect = document.getElementById('student_id');
    const subjectInput = document.getElementById('subject');
    const tableBody = document.getElementById('marks-table-body');
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const markId = urlParams.get('id');

    // 1. Fetch Students List & Faculty Subject Specialization for Form
    fetch('../backend/marks.php?action=get_students')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Faculty.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success) {
                if (subjectInput && !subjectInput.value && res.faculty_subject) {
                    subjectInput.value = res.faculty_subject;
                }

                if (studentSelect && res.students) {
                    studentSelect.innerHTML = '<option value="">-- Choose Student --</option>' +
                        res.students.map(s => `
                            <option value="${s.id}">[${escapeHtml(s.roll_number)}] ${escapeHtml(s.full_name)} (${escapeHtml(s.class_name)})</option>
                        `).join('');

                    // If in Edit Mode, fetch mark details after student options are loaded
                    if (action === 'edit' && markId) {
                        fetchMarkForEdit(markId);
                    }
                }
            }
        })
        .catch(err => console.error('Error initializing marks form:', err));

    function fetchMarkForEdit(id) {
        fetch(`../backend/marks.php?action=get&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if (res && res.success && res.data) {
                    const m = res.data;
                    document.getElementById('mark_id').value = m.id;
                    studentSelect.value = m.student_id;
                    subjectInput.value = m.subject || '';
                    document.getElementById('exam_name').value = m.exam_name || '';
                    document.getElementById('marks_obtained').value = m.marks_obtained || '';
                    document.getElementById('max_marks').value = m.max_marks || 100;
                    document.getElementById('recorded_date').value = m.recorded_date || '';

                    document.getElementById('form-heading').textContent = 'Edit Student Marks Entry';
                    document.getElementById('submit-btn').textContent = 'Update Marks Record';
                    const cancelBtn = document.getElementById('cancel-edit-btn');
                    if (cancelBtn) cancelBtn.style.display = 'inline-block';
                }
            })
            .catch(err => console.error('Error fetching mark record:', err));
    }

    // 2. Fetch and render recorded marks list
    fetch('../backend/marks.php?action=list')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Faculty.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                if (res.data.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-muted); padding: 25px;">No marks recorded yet. Select a student above to record exam marks.</td></tr>`;
                    return;
                }

                tableBody.innerHTML = res.data.map(m => {
                    const gradeClass = (m.grade === 'F') ? 'badge-absent' : 'badge-present';

                    return `
                        <tr>
                            <td>
                                <strong>${escapeHtml(m.full_name)}</strong>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">${escapeHtml(m.roll_number)} (${escapeHtml(m.class_name)})</div>
                            </td>
                            <td>${escapeHtml(m.subject)}</td>
                            <td><strong>${escapeHtml(m.exam_name)}</strong></td>
                            <td>${parseFloat(m.marks_obtained).toFixed(1)} / ${parseFloat(m.max_marks).toFixed(0)}</td>
                            <td><strong>${m.percentage}%</strong></td>
                            <td><span class="badge-status ${gradeClass}">${escapeHtml(m.grade)}</span></td>
                            <td>${formatDate(m.recorded_date)}</td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="marks.html?action=edit&id=${m.id}" class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="../backend/marks.php?action=delete&id=${m.id}" 
                                       class="btn btn-sm btn-danger btn-delete" 
                                       data-confirm="Are you sure you want to delete this mark record?">Delete</a>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        })
        .catch(err => {
            console.error('Error loading marks:', err);
            tableBody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: #dc2626; padding: 20px;">Error loading marks records.</td></tr>`;
        });
}


// Page 9: Faculty View Circulars (faculty/notices.html)

function initFacultyNotices() {
    const container = document.getElementById('faculty-notices-container');
    if (!container) return;

    fetch('../backend/notices.php?action=list')
        .then(res => {
            if (res.status === 401) {
                window.location.href = '../login.html?error=' + encodeURIComponent('Please log in as Faculty.');
                return;
            }
            return res.json();
        })
        .then(res => {
            if (res && res.success && res.data) {
                if (res.data.length === 0) {
                    container.innerHTML = '<p style="color: var(--text-muted); padding: 15px 0;">No circulars or notices are currently available.</p>';
                    return;
                }

                container.innerHTML = res.data.map(n => `
                    <div class="notice-item" style="padding: 16px 0; border-bottom: 1px solid #e2e8f0;">
                        <div class="notice-header">
                            <span class="notice-title" style="font-size: 1.15rem;">${escapeHtml(n.title)}</span>
                            <span class="notice-date">Published: ${formatDate(n.posted_date)}</span>
                        </div>
                        <div class="notice-content" style="font-size: 0.95rem; margin: 8px 0;">
                            ${escapeHtml(n.content).replace(/\n/g, '<br>')}
                        </div>
                        <div class="notice-author">
                            Issued by: <strong>${escapeHtml(n.posted_by)}</strong>
                        </div>
                    </div>
                `).join('');
            }
        })
        .catch(err => {
            console.error('Error fetching notices:', err);
            container.innerHTML = '<p style="color: #dc2626; padding: 15px 0;">Error loading school notices.</p>';
        });
}
