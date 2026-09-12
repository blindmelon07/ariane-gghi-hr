@echo off
REM Builds AttendancePuller.exe — a standalone Windows executable that
REM bundles Python + all dependencies, so it can run on a desktop that
REM doesn't have Python installed at all.
REM
REM Run this once (or whenever attendance_puller.py changes):
REM     build.bat
REM
REM Output: dist\AttendancePuller.exe — copy that file (and a config.ini,
REM see config.example.ini) to wherever it needs to run.

pip install -r requirements.txt pyinstaller || goto :error

pyinstaller --onefile --name AttendancePuller --console --clean attendance_puller.py || goto :error

echo.
echo Build complete: dist\AttendancePuller.exe
echo Copy config.example.ini next to it as config.ini and fill in your values.
goto :eof

:error
echo.
echo Build failed — see errors above.
exit /b 1
