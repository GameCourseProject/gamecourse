import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-virtualcurrency',
  templateUrl: './virtualcurrency.component.html',
  styleUrls: ['./virtualcurrency.component.scss']
})
export class VirtualcurrencyComponent implements OnInit {

  loading: boolean;

  courseID: number;
  courseFolder: string;

  constructor() { }

  ngOnInit(): void {
  }

}
