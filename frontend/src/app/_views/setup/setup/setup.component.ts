import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {Router} from "@angular/router";
import {ErrorService} from "../../../_services/error.service";

@Component({
  selector: 'app-setup',
  templateUrl: './setup.component.html',
  styleUrls: ['./setup.component.scss']
})
export class SetupComponent implements OnInit {

  form: FormGroup;
  loading: boolean;

  constructor(
    private api: ApiHttpService,
    private fb: FormBuilder,
    private router: Router
  ) {
  }

  ngOnInit(): void {
    this.form = this.fb.group({
      courseName: ['', Validators.required],
      courseColor: ['', Validators.required],
      teacherId: ['', Validators.required],
      teacherUsername: ['', Validators.required],
    });
  }

  onSubmit(): void {
    if (this.form.valid) {
      this.loading = true;

      this.api.doSetup(this.form.getRawValue())
        .subscribe(
          setup => {
            if (setup) this.router.navigate(['']);
            },
            error => ErrorService.set(error),
          () => this.loading = false
      );
    }
  }
}

export interface SetupData {
  courseName: string,
  courseColor: string,
  teacherId: number,
  teacherUsername: string
}
