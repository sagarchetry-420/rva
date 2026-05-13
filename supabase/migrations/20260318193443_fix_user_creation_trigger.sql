-- Update the handle_new_user trigger to also insert into role-specific tables
-- This ensures teachers/students/staff are properly inserted into their respective tables

CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  user_role TEXT;
  user_class_id UUID;
  user_subject_id UUID;
  user_dob DATE;
  user_department TEXT;
  user_hire_date DATE;
  new_teacher_id UUID;
BEGIN
  -- Extract role and other metadata from user_metadata
  user_role := NEW.raw_user_meta_data->>'role';
  user_class_id := (NEW.raw_user_meta_data->>'class_id')::UUID;
  user_subject_id := (NEW.raw_user_meta_data->>'subject_id')::UUID;
  user_dob := (NEW.raw_user_meta_data->>'dob')::DATE;
  user_department := NEW.raw_user_meta_data->>'department';
  user_hire_date := COALESCE((NEW.raw_user_meta_data->>'hire_date')::DATE, CURRENT_DATE);

  -- Create profile for the user
  INSERT INTO public.profiles (user_id, first_name, last_name)
  VALUES (
    NEW.id,
    COALESCE(NEW.raw_user_meta_data->>'first_name', ''),
    COALESCE(NEW.raw_user_meta_data->>'last_name', '')
  );

  -- Insert into user_roles if role is specified
  IF user_role IS NOT NULL AND user_role IN ('admin', 'teacher', 'student', 'staff') THEN
    INSERT INTO public.user_roles (user_id, role)
    VALUES (NEW.id, user_role::app_role);
  END IF;

  -- Insert into role-specific table based on user role
  IF user_role = 'teacher' THEN
    INSERT INTO public.teachers (user_id, department, hire_date)
    VALUES (NEW.id, COALESCE(user_department, 'Unassigned'), user_hire_date)
    RETURNING id INTO new_teacher_id;

    -- Also create teacher_subjects mapping if class_id and subject_id are provided
    IF user_class_id IS NOT NULL AND user_subject_id IS NOT NULL THEN
      INSERT INTO public.teacher_subjects (teacher_id, subject_id, class_id)
      VALUES (new_teacher_id, user_subject_id, user_class_id);
    END IF;
  ELSIF user_role = 'student' THEN
    INSERT INTO public.students (user_id, class_id, dob)
    VALUES (NEW.id, user_class_id, user_dob);
  ELSIF user_role = 'staff' THEN
    INSERT INTO public.staff (user_id, job_title)
    VALUES (NEW.id, COALESCE(NEW.raw_user_meta_data->>'job_title', ''));
  END IF;

  RETURN NEW;
END;
$$;
