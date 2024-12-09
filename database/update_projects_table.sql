-- Add new columns to projects table
ALTER TABLE projects
ADD COLUMN project_abbreviation VARCHAR(10) AFTER project_name,
ADD COLUMN project_year YEAR AFTER project_abbreviation,
ADD COLUMN database_name VARCHAR(64) AFTER abstract;

-- Update existing records with default values
UPDATE projects 
SET project_abbreviation = UPPER(SUBSTRING(project_name, 1, 3)),
    project_year = YEAR(upload_date),
    database_name = CONCAT('db_', LOWER(SUBSTRING(project_name, 1, 10)))
WHERE project_abbreviation IS NULL;
