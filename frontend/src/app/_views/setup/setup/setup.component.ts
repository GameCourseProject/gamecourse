import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ApiHttpService} from "../../../_services/api/api-http.service";
import {throwError} from "rxjs";
import {Router} from "@angular/router";

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
      const formData = this.form.getRawValue();

      this.api.doSetup(formData).subscribe(
        res => {
          this.loading = false;
          this.router.navigate(['/']);
          console.log(res)
        },
        error => throwError(error)
      );
    }
  }
}
