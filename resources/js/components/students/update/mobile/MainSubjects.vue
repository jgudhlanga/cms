<script setup lang="ts"></script>

<template>
    <div class="mobile-subjects-container">
        <div class="subject-card">
            <div class="card-header">
                <h3 class="card-title subject-name">Mathematics</h3>
                <div class="status-indicator status-complete"><i class="fas fa-check-circle"></i> Complete</div>
            </div>
            <div class="card-content">
                <div class="form-row">
                    <label class="form-label">Year</label>
                    <select class="mobile-select">
                        <option>2023</option>
                        <option selected>2022</option>
                        <option>2021</option>
                        <option>2020</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Sitting</label>
                    <select class="mobile-select">
                        <option>May/June</option>
                        <option selected>October/November</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Grade</label>
                    <div class="grade-section">
                        <div class="mobile-radio-group">
                            <label class="radio-option">
                                <input type="radio" name="math-grade-mobile" class="radio-input" value="A" />
                                <span class="radio-label">A</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="math-grade-mobile" class="radio-input" value="B" checked />
                                <span class="radio-label">B</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="math-grade-mobile" class="radio-input" value="C" />
                                <span class="radio-label">C</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- English Language Card -->
        <div class="subject-card">
            <div class="card-header">
                <h3 class="card-title subject-name">English Language</h3>
                <div class="status-indicator status-complete"><i class="fas fa-check-circle"></i> Complete</div>
            </div>

            <div class="card-content">
                <div class="form-row">
                    <label class="form-label">Year</label>
                    <select class="mobile-select">
                        <option>2023</option>
                        <option>2022</option>
                        <option selected>2021</option>
                        <option>2020</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Sitting</label>
                    <select class="mobile-select">
                        <option selected>May/June</option>
                        <option>October/November</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Grade</label>
                    <div class="grade-section">
                        <div class="mobile-radio-group">
                            <label class="radio-option">
                                <input type="radio" name="english-grade-mobile" class="radio-input" value="A" checked />
                                <span class="radio-label">A</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="english-grade-mobile" class="radio-input" value="B" />
                                <span class="radio-label">B</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="english-grade-mobile" class="radio-input" value="C" />
                                <span class="radio-label">C</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Physics Card -->
        <div class="subject-card">
            <div class="card-header">
                <h3 class="card-title subject-name">Physics</h3>
                <div class="status-indicator status-incomplete"><i class="fas fa-exclamation-circle"></i> Incomplete</div>
            </div>

            <div class="card-content">
                <div class="form-row">
                    <label class="form-label">Year</label>
                    <select class="mobile-select">
                        <option selected>2023</option>
                        <option>2022</option>
                        <option>2021</option>
                        <option>2020</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Sitting</label>
                    <select class="mobile-select">
                        <option selected>May/June</option>
                        <option>October/November</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label">Grade</label>
                    <div class="grade-section">
                        <div class="mobile-radio-group">
                            <label class="radio-option">
                                <input type="radio" name="physics-grade-mobile" class="radio-input" value="A" />
                                <span class="radio-label">A</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="physics-grade-mobile" class="radio-input" value="B" />
                                <span class="radio-label">B</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="physics-grade-mobile" class="radio-input" value="C" checked />
                                <span class="radio-label">C</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.mobile-subjects-container {
    display: none;
    flex-direction: column;
    gap: 16px;
}

.subject-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid #e1e5e9;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}

.subject-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.bulk-edit-card {
    border: 2px solid #3b82f6;
    background: #f8fafc;
}

.card-header {
    padding: 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e1e5e9;
}

.bulk-edit-card .card-header {
    background: #e8f0fe;
}

.card-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.bulk-edit-card .card-title {
    color: #3b82f6;
    font-weight: 700;
}

.card-content {
    padding: 16px;
}

.form-row {
    margin-bottom: 20px;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 8px;
}

.mobile-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 15px;
    background-color: white;
    color: #374151;
    cursor: pointer;
}

.mobile-select:focus {
    outline: none;
    border-color: #4a6ee0;
    box-shadow: 0 0 0 3px rgba(74, 110, 224, 0.2);
}

.grade-section {
    width: 100%;
}

.grade-spinner {
    display: flex;
    justify-content: center;
    padding: 16px 0;
    color: #4a6ee0;
}

.mobile-radio-group {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.radio-option {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
}

.radio-option:last-child {
    border-bottom: none;
}

.radio-input {
    margin-right: 12px;
    width: 20px;
    height: 20px;
    accent-color: #4a6ee0;
}

.radio-label {
    font-size: 15px;
    font-weight: 500;
    text-transform: uppercase;
}

.subject-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 16px;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    margin-top: 10px;
}

.status-complete {
    background-color: #d1fae5;
    color: #065f46;
}

.status-incomplete {
    background-color: #fee2e2;
    color: #991b1b;
}

.footer {
    text-align: center;
    margin-top: 40px;
    padding: 20px;
    color: #6b7280;
    font-size: 14px;
    border-top: 1px solid #e5e7eb;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .mobile-subjects-container {
        display: none;
    }

    .desktop-table {
        display: table;
    }
}

@media (max-width: 767px) {
    .desktop-table {
        display: none;
    }

    .mobile-subjects-container {
        display: flex;
    }

    header {
        padding: 15px;
    }

    h1 {
        font-size: 24px;
    }
}

@media (max-width: 480px) {
    .mobile-subjects-container {
        padding: 0;
        gap: 12px;
    }

    .card-header {
        padding: 12px;
    }

    .card-content {
        padding: 12px;
    }

    .form-row {
        margin-bottom: 16px;
    }

    .card-title {
        font-size: 15px;
    }

    .form-label {
        font-size: 13px;
    }

    .mobile-select {
        padding: 10px;
        font-size: 14px;
    }
}

.toggle-container {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.toggle-btn {
    padding: 10px 20px;
    background: #4a6ee0;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.3s;
}

.toggle-btn:hover {
    background: #3a5ecb;
}
</style>
