import subprocess
import time
import argparse

## How to run
# sudo -u www-data python3 benchmark_autogame.py <course_number>

def run_benchmark(course_number):
    command = ['php',
               '/var/www/html/gamecourse_test/api/models/GameCourse/AutoGame/AutoGameScriptManual.php',
               str(course_number)]

    start_time = time.time()

    try:
        # Run the command
        result = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)

        # Calculate the elapsed time
        elapsed_time = time.time() - start_time

        if result.returncode != 0:
            print("Something went wrong with Autogame execution")
        else:
            if elapsed_time > 180:
                print(f"[WARNING] Execution Time higher than 3 minutes. Please check modifications tat may have impacted performance")
                print(f"Total time: {elapsed_time}")
            else:
                print(f"[SUCCESS] Autogame run in {elapsed_time} seconds")

    except subprocess.TimeoutExpired:
        print("Command execution exceeded 2 minutes and was terminated.")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Benchmark test for AutoGameScriptManual.php.')
    parser.add_argument('course_number', type=int, help='Course number to run the Autogame.')

    args = parser.parse_args()
    run_benchmark(args.course_number)
