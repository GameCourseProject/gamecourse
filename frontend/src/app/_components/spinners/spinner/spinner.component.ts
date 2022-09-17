import {Component, Input, OnInit} from '@angular/core';

@Component({
  selector: 'app-spinner',
  templateUrl: './spinner.component.html',
  styleUrls: ['./spinner.component.scss']
})
export class SpinnerComponent implements OnInit {

  @Input() color?: string = 'primary';          // Color
  @Input() size?: 'sm' | 'md' | 'lg' = 'md';    // Size

  @Input() classList?: string;                  // Classes to add

  sizes = {
    sm: 'h-5 w-5',
    md: 'h-8 w-8',
    lg: 'h-12 w-12'
  }

  constructor() { }

  ngOnInit(): void {
  }

}
