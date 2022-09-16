import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-spinner',
  templateUrl: './spinner.component.html',
  styleUrls: ['./spinner.component.scss']
})
export class SpinnerComponent implements OnInit {

  @Input() color?: string = 'primary';    // Color
  @Input() size?: number = 8;             // Size

  @Input() classList?: string;            // Classes to add

  constructor() { }

  ngOnInit(): void {
  }

}
