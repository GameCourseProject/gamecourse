import { Component, OnInit } from '@angular/core';
import {ApiHttpService} from "../../../../../../../_services/api/api-http.service";
import {ActivatedRoute} from "@angular/router";
import {Tier} from "../../../../../../../_domain/skills/tier";
import {finalize} from "rxjs/operators";
import {ErrorService} from "../../../../../../../_services/error.service";
import {exists} from "../../../../../../../_utils/misc/misc";
import {Skill} from "../../../../../../../_domain/skills/skill";
import Pickr from "@simonwep/pickr";
import {Subject} from "rxjs";

@Component({
  selector: 'app-skills',
  templateUrl: './skills.component.html',
  styleUrls: ['./skills.component.scss']
})
export class SkillsComponent implements OnInit {

  loading: boolean;

  courseID: number;
  tiers: Tier[] = [];
  skills: Skill[] = [];

  pickr: Pickr;

  mode: 'add' | 'edit';

  newTier: TierData = {
    tier: null,
    seqId: null,
    reward: null,
    treeId: null
  };
  tierToEdit: Tier;
  tierToDelete: Tier;

  newSkill: SkillData = {
    name: null,
    color: null,
    description: null,
    tier: null,
    dependencies: null,
    dependenciesList: [],
  };
  skillToEdit: Skill;
  skillToDelete: Skill;

  isTierModalOpen: boolean;
  isSkillModalOpen: boolean;
  skillModalRendered: Subject<void> = new Subject<void>();
  isDeleteVerificationModalOpen: boolean;
  isImportModalOpen: boolean;
  isSkillPreviewModalOpen: boolean;
  saving: boolean;

  selectedDependency1: Skill = null;
  selectedDependency2: Skill = null;

  constructor(
    private api: ApiHttpService,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.loading = true;
    this.route.parent.params.subscribe(params => {
      this.courseID = parseInt(params.id);
      this.getTiers();
      this.getSkills();
    });
  }


  /*** --------------------------------------------- ***/
  /*** -------------------- Init ------------------- ***/
  /*** --------------------------------------------- ***/

  getTiers() {
    this.loading = true;
    this.api.getTiers(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        tiers => this.tiers = tiers,
        error => ErrorService.set(error)
      );
  }

  getSkills() {
    this.loading = true;
    this.api.getSkills(this.courseID)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        skills => this.skills = skills,
        error => ErrorService.set(error)
      );
  }


  /*** --------------------------------------------- ***/
  /*** ------------------ Actions ------------------ ***/
  /*** --------------------------------------------- ***/

  createTier(): void {
    this.loading = true;

    this.api.createTier(this.courseID, this.newTier)
      .pipe( finalize(() => {
        this.isTierModalOpen = false;
        this.clearObject(this.newTier);
        this.loading = false;
      }) )
      .subscribe(
        res => this.getTiers(),
        error => ErrorService.set(error)
      )
  }

  editTier(): void {
    this.loading = true;
    this.newTier['id'] = this.tierToEdit.id;

    this.api.editTier(this.courseID, this.newTier)
      .pipe( finalize(() => {
        this.isTierModalOpen = false;
        this.clearObject(this.newTier);
        this.loading = false;
      }) )
      .subscribe(
        res => {
          this.getTiers();
        },
        error => ErrorService.set(error)
      )
  }

  deleteTier(): void {
    this.loading = true;
    this.api.deleteTier(this.courseID, this.tierToDelete)
      .pipe( finalize(() => {
        this.tierToDelete = null;
        this.skillToDelete = null;
        this.isDeleteVerificationModalOpen = false;
        this.loading = false;
      }) )
      .subscribe(
        res => {
          this.getTiers();
        },
        error => ErrorService.set(error)
      )
  }

  createSkill(): void {
    this.loading = true;

    this.api.createSkill(this.courseID, this.newSkill)
      .pipe( finalize(() => {
        this.isSkillModalOpen = false;
        this.clearObject(this.newSkill);
        this.selectedDependency1 = null;
        this.selectedDependency2 = null;
        this.loading = false;
      }) )
      .subscribe(
        res => this.getSkills(),
        error => ErrorService.set(error)
      )
  }

  editSkill(): void {
    this.loading = true;
    this.newSkill['id'] = this.skillToEdit.id;

    this.api.editSkill(this.courseID, this.newSkill)
      .pipe( finalize(() => {
        this.isSkillModalOpen = false;
        this.clearObject(this.newSkill);
        this.selectedDependency1 = null;
        this.selectedDependency2 = null;
        this.loading = false;
      }) )
      .subscribe(
        res => this.getSkills(),
        error => ErrorService.set(error)
      )
  }

  deleteSkill(): void {
    this.loading = true;
    this.api.deleteSkill(this.courseID, this.skillToDelete)
      .pipe( finalize(() => {
        this.tierToDelete = null;
        this.skillToDelete = null;
        this.isDeleteVerificationModalOpen = false;
        this.loading = false
      }) )
      .subscribe(
        res => this.getSkills(),
        error => ErrorService.set(error)
      )
  }

  toggleSkill(skillId: number) {
    this.loading = true;
    this.api.toggleItemParam(this.courseID, ApiHttpService.SKILLS, skillId, 'isActive')
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => {},
        error => ErrorService.set(error)
      )
  }

  moveItem(item: Tier | Skill, direction: number, type: 'tier' | 'skill') {
    const oldSeq = item.seqId;
    let newSeq;

    if (direction > 0) { // move up
      if (oldSeq === 1) return;
      newSeq = oldSeq - 1;

    } else if (direction < 0) { // move down
      const len = type === 'tier' ? this.tiers.length : this.skills.length;
      if (oldSeq === len) return;
      newSeq = oldSeq < len ? oldSeq + 1 : len;
    }

    this.loading = true;
    this.api.changeItemSequence(this.courseID, ApiHttpService.SKILLS, item.id, oldSeq, newSeq, type)
      .pipe( finalize(() => this.loading = false) )
      .subscribe(
        res => {
          this.getTiers();
          if (oldSeq === 1 || newSeq === 1) this.getSkills();
        },
        error => ErrorService.set(error)
      )
  }

  exportAllSkills() {
    // TODO
  }

  addSkillDependency(dep1: Skill, dep2: Skill) {
    if (!this.newSkill.dependencies) this.newSkill.dependencies = dep1.name + ' + ' + dep2.name;
    else this.newSkill.dependencies += ' | ' + dep1.name + ' + ' + dep2.name;

    this.newSkill.dependenciesList.push([dep1.name, dep2.name]);
  }

  removeSkillDependency(dependencyPair: string[]) {
    this.newSkill.dependenciesList = this.newSkill.dependenciesList.filter(pair => {
      return !(dependencyPair.includes(pair[0]) && dependencyPair.includes(pair[1]));
    });

    if (this.newSkill.dependenciesList.length === 0) this.newSkill.dependencies = "";
    else {
      this.newSkill.dependencies = this.newSkill.dependenciesList[0][0] + ' + ' + this.newSkill.dependenciesList[0][1];
      for (let i = 1; i < this.newSkill.dependenciesList.length; i++) {
        const pair = this.newSkill.dependenciesList[i];
        this.newSkill.dependencies += ' | ' + pair[0] + ' + ' + pair[1];
      }
    }
  }

  /*** --------------------------------------------- ***/
  /*** ------------------ Helpers ------------------ ***/
  /*** --------------------------------------------- ***/

  initEditItem(item: any, type: 'tier' | 'skill'): void {
    if (type === 'tier') {
      this.newTier = {
        tier: item.tier,
        seqId: item.seqId,
        reward: item.reward,
        treeId: item.treeId
      };
      this.tierToEdit = item;
    }

    if (type === 'skill') {
      this.newSkill = {
        name: item.name,
        color: item.color,
        description: item.description,
        tier: item.tier,
        dependencies: item.dependencies,
        dependenciesList: item.dependenciesList,
      };
      this.skillToEdit = item;
    }
  }

  clearObject(obj): void {
    for (const key of Object.keys(obj)) {
      obj[key] = null;
    }
  }

  isReadyToSubmit(type: 'tier' | 'skill') {
    let isValid = function (text) {
      return exists(text) && !text.toString().isEmpty();
    }

    // Validate inputs
    if (type === 'tier') return isValid(this.newTier.tier) && isValid(this.newTier.reward);
    else if (type === 'skill') return isValid(this.newSkill.tier) && isValid(this.newSkill.name) && isValid(this.newSkill.color);
      // && isValid(this.newSkill.description); FIXME
    return true;
  }

  isWhite(color: string): boolean {
    if (!color) return false;
    return ['white', '#ffffff', '#fff'].includes(color.toLowerCase());
  }

  initColorPicker(): void {
    setTimeout(() => {
      // Simple example, see optional options for more configuration.
      this.pickr = Pickr.create({
        el: '#new_pickr',
        useAsButton: true,
        default: this.mode === 'add' ? 'white' : this.newSkill.color,
        theme: 'monolith', // or 'classic', or 'nano',
        components: {
          hue: true,
          interaction: {
            input: true,
            save: true
          }
        }
      }).on('init', pickr => {
        this.newSkill.color = pickr.getSelectedColor().toHEXA().toString(0);
      }).on('save', color => {
        this.newSkill.color = color.toHEXA().toString(0);
        this.pickr.hide();
      }).on('change', color => {
        this.newSkill.color = color.toHEXA().toString(0);
      });
    }, 0);
  }

  filterSkills(id: number): Skill[] {
    return this.skills.filter(skill => skill.id !== id);
  }

  initTextEditor() {
    setTimeout(() => this.skillModalRendered.next(), 0);
  }
}

export interface TierData {
  id?: number,
  tier: string,
  seqId: number,
  reward: number,
  treeId: number,
}

export interface SkillData {
  id?: number,
  name: string,
  color: string,
  description: string,
  tier: string,
  dependencies: string,
  dependenciesList: string[][],
}
