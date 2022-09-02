import { Component, OnInit } from '@angular/core';
import {Router} from "@angular/router";

@Component({
  selector: 'app-rules',
  templateUrl: './rules.component.html',
  styleUrls: ['./rules.component.scss']
})
export class RulesComponent implements OnInit {

  loading: boolean;

  constructor(
    private router: Router,

  ) {

  }

  ngOnInit(): void {
  }


}
