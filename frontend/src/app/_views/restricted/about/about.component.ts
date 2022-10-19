import {Component, OnInit, ViewChild} from '@angular/core';
import { NgForm } from '@angular/forms';
import { ModalService } from 'src/app/_services/modal.service';
import {Subject} from "rxjs";

@Component({
  selector: 'app-about',
  templateUrl: './about.component.html',
  styleUrls: ['./about.component.scss']
})
export class AboutComponent implements OnInit {

  value;
  value2;
  @ViewChild('f', { static: false }) f: NgForm;

  setSelected: Subject<any[]> = new Subject<any[]>();

  constructor() { }

  ngOnInit(): void {
  }

  test() {
    ModalService.openModal('verification-modal')
  }

  onSubmit() {
    if (this.f.form.valid) {
      console.log('valid')

    } else {
      console.log('invalid')
      console.log(this.f.errors)
    }
  }
}
