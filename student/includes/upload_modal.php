            <!-- Upload Modal -->
            <div class="modal fade" id="upload-modal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Upload New System Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="student.php" method="post" enctype="multipart/form-data">
                            <div class="modal-body">
                                <!-- Project Title -->
                                <div class="mb-3">
                                    <label for="project_name" class="form-label">Project Title</label>
                                    <input type="text" name="project_name" class="form-control" required>
                                </div>

                                <!-- Project Abbreviation -->
                                <div class="mb-3">
                                    <label for="project_abbreviation" class="form-label">Project Abbreviation</label>
                                    <input type="text" name="project_abbreviation" class="form-control" 
                                           pattern="[A-Za-z]+" title="Only letters allowed" required>
                                    <div class="form-text">Example: ABC (only letters allowed)</div>
                                </div>

                                <!-- Project Year -->
                                <div class="mb-3">
                                    <label for="project_year" class="form-label">Year</label>
                                    <select name="project_year" class="form-control" required>
                                        <?php
                                        $currentYear = date('Y');
                                        for ($year = $currentYear; $year >= 2020; $year--) {
                                            echo "<option value=\"$year\">$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Project Members -->
                                <div class="mb-3">
                                    <label for="project_members" class="form-label">Project Members</label>
                                    <textarea name="project_members" class="form-control" rows="2" 
                                            placeholder="Enter member names, separated by commas" required></textarea>
                                    <div class="form-text">Separate multiple members with commas</div>
                                </div>

                                <!-- Abstract -->
                                <div class="mb-3">
                                    <label for="abstract" class="form-label">Abstract</label>
                                    <textarea name="abstract" class="form-control" rows="4" required></textarea>
                                </div>

                                <!-- Database Name -->
                                <div class="mb-3">
                                    <label for="database_name" class="form-label">Database Name</label>
                                    <input type="text" name="database_name" class="form-control" 
                                           pattern="[a-zA-Z0-9_]+" title="Only letters, numbers, and underscores allowed" required>
                                    <div class="form-text">Example: project_db (only letters, numbers, and underscores)</div>
                                </div>

                                <!-- MySQL Database File -->
                                <div class="mb-3">
                                    <label for="database_file" class="form-label">MySQL Database File</label>
                                    <input type="file" name="database_file" class="form-control" accept=".sql" required>
                                    <div class="form-text">Upload .sql file exported from phpMyAdmin</div>
                                </div>

                                <!-- Project Photo -->
                                <div class="mb-3">
                                    <label for="project_photo" class="form-label">System Project Photo</label>
                                    <input type="file" name="project_photo" class="form-control" accept="image/*" required>
                                    <div class="form-text">Recommended: Square image (800x800 pixels)</div>
                                </div>

                                <!-- Project ZIP File -->
                                <div class="mb-3">
                                    <label for="project_file" class="form-label">System Project ZIP File</label>
                                    <input type="file" name="project_file" class="form-control" accept=".zip" required>
                                    <div class="form-text">Upload your project files as a ZIP archive</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-cloud-upload"></i> Upload Project
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>