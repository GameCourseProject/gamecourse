import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-main',
  templateUrl: './main.component.html',
  styleUrls: ['./main.component.scss']
})
export class MainComponent implements OnInit {

  loading = true;

  activeCourses; // TODO: get actual courses

  constructor() { }

  ngOnInit(): void {
    setTimeout(() => {
      this.getUserActiveCourses();
      this.loading = false;
    }, 800);
  }

  getUserActiveCourses(): any {
    this.activeCourses = [
      {name: 'Multimedia Content Production'}
    ];
  }

}
