import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-no-access',
  templateUrl: './no-access.component.html'
})
export class NoAccessComponent implements OnInit {

  constructor() { }

  ngOnInit(): void {
  }

  goBack() {
    history.back();
  }

}
