<?php include 'admin_header.php'; ?>

<div style="max-width: 800px; margin: 0 auto;" class="animate-content">
    <div style="margin-bottom: 35px;">
        <h1 style="font-size: 2.2rem; font-weight: 800; color: #0f172a;">Create New Exam</h1>
        <p style="color: #64748b; font-size: 1.1rem;">Set up the basic details for your new assessment.</p>
    </div>

    <div class="card">
        <form action="save_new_exam_action.php" method="POST" style="display: flex; flex-direction: column; gap: 25px;">
            <div>
                <label style="display:block; margin-bottom:10px; font-weight:700; color:#334155;">Exam Title</label>
                <input type="text" name="exam_title" placeholder="e.g., PHP Final Certification" required style="font-size: 1.1rem;">
            </div>

            <div>
                <label style="display:block; margin-bottom:10px; font-weight:700; color:#334155;">Time Limit (Minutes)</label>
                <input type="number" name="duration" placeholder="e.g., 45" required style="font-size: 1.1rem;">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <button type="submit" class="btn-primary" style="flex: 2; padding: 18px; font-size: 1.1rem;">Create & Add Questions</button>
                <a href="manage_exams.php" style="flex: 1; text-align: center; padding: 18px; border-radius: 12px; background: #f1f5f9; color: #64748b; text-decoration: none; font-weight: 700; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'admin_footer.php'; ?>