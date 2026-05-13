-- Simplify the handle_new_user trigger to avoid blocking auth user creation
-- Teacher/student/staff records will be created by the API instead

CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public
AS $$
DECLARE
  user_role TEXT;
BEGIN
  -- Extract role from user_metadata
  user_role := NEW.raw_user_meta_data->>'role';

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

  RETURN NEW;
END;
$$;
