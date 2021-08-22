import {Component, OnInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {ApiHttpService} from "../../_services/api-http.service";
import {ApiEndpointsService} from "../../_services/api-endpoints.service";

@Component({
  selector: 'app-setup',
  templateUrl: './setup.component.html',
  styleUrls: ['./setup.component.scss']
})
export class SetupComponent implements OnInit {

  form: FormGroup;

  constructor(
    private apiHttpService: ApiHttpService,
    private apiEndpointsService: ApiEndpointsService,
    private fb: FormBuilder
  ) { }

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
      const formData = this.form.getRawValue();

      this.apiHttpService
        .post(this.apiEndpointsService.getSetupEndpoint(), formData)
        .subscribe(
          res => console.log(res),
          error => console.log(error)
        );
    }
  }
}
