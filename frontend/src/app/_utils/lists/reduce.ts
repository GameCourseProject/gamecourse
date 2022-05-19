import {Course} from "../../_domain/courses/course";
import {User} from "../../_domain/users/user";
import {Module} from "../../_domain/modules/module";
import {Page} from "../../_domain/pages & templates/page";
import {Template} from "../../_domain/pages & templates/template";
import {exists} from "../misc/misc";

/**
 * This class is responsible for reducing a list of items,
 * either by searching, filtering or both at the same time.
 */
export class Reduce {

  private _query: string = null;      // Search query
  private _filters: string[] = [];    // Active filters
  private _items: any[] = [];         // Items list reduced

  get query(): string {
    return this._query;
  }

  private set query(value: string) {
    this._query = value;
  }

  get filters(): string[] {
    return this._filters;
  }

  private set filters(value: string[]) {
    this._filters = value;
  }

  get items(): any[] {
    return this._items;
  }

  private set items(value: any[]) {
    this._items = value;
  }

  /**
   * Search for a query in the list of items.
   *
   * @param items
   * @param query
   */
  public search(items: any[], query?: string) {
    if (exists(query))
      this.query = query;

    this.items = [];
    items.forEach(item => {
      if (this.isQueryTrueSearch(item))
        this.items.push(item);
    });
  }

  /**
   * Filter a list of items.
   *
   * @param items
   * @param filters
   */
  public filter(items: any[], filters?: string[]) {
    if (exists(filters))
      this.filters = filters;

    this.items = [];
    items.forEach(item => {
      if (this.isQueryTrueFilter(item))
        this.items.push(item);
    });
  }

  /**
   * Search and filter a list of items.
   *
   * @param items
   * @param query
   * @param filters
   */
  public searchAndFilter(items: any[], query?: string, filters?: string[]) {
    if (exists(query))
      this.query = query;

    if (exists(filters))
      this.filters = filters;

    this.items = [];
    items.forEach(item => {
      if (this.isQueryTrueSearch(item) && this.isQueryTrueFilter(item))
        this.items.push(item);
    });
  }

  /**
   * Update a filter's state
   *
   * @param filter
   * @param state
   */
  public updateFilter(filter: string, state: boolean) {
    if (state) {
      this.filters.push(filter)

    } else {
      const index = this.filters.findIndex(el => el === filter);
      this.filters.splice(index, 1);
    }
  }


  /**
   * ------------------------
   * Helper functions
   * ------------------------
   */

  private isQueryTrueSearch(item: any): boolean {
    return !this.query ||
      (item instanceof Course && Search.onCourse(item, this.query)) ||
      (item instanceof User && Search.onUser(item, this.query)) ||
      (item instanceof Module && Search.onModule(item, this.query)) ||
      ((item instanceof Page || item instanceof Template) && Search.onPageOrTemplate(item, this.query));
  }

  private isQueryTrueFilter(item: any): boolean {
    if (this.filters.length === 0) return true;

    for (const filter of this.filters) {
      if ( (item instanceof Course && Filter.onCourse(item, filter)) ||
        (item instanceof User && Filter.onUser(item, filter)) )
        return true;
    }
    return false;
  }
}

/**
 * This class has utility functions to search through
 * specific types of items.
 *
 * It is meant to be used only by Reduce and tests.
 * @class Reduce
 */
export class Search {

  private static readonly IGNORED = /[\s,.:;\-_/\\!|+*"']+/g;

  /**
   * Search on a Course.
   * @class Course
   *
   * @param course
   * @param query
   */
  public static onCourse(course: Course, query: string): boolean {
    return (course.name && this.search(course.name, query)) ||
      (course.short && this.search(course.short, query)) ||
      (course.year && this.search(course.year, query));
  }

  /**
   * Search on a User.
   * @class User
   *
   * @param user
   * @param query
   */
  public static onUser(user: User, query: string): boolean {
    return (user.name && this.search(user.name, query)) ||
      (user.nickname && this.search(user.nickname, query)) ||
      (user.studentNumber && this.search(user.studentNumber.toString(), query)) ||
      (user.email && this.search(user.email, query)) ||
      (user.username && this.search(user.username, query));
  }

  /**
   * Search on a Module.
   * @class Module
   *
   * @param module
   * @param query
   */
  public static onModule(module: Module, query: string): boolean {
    return (module.id && this.search(module.id, query)) ||
      (module.name && this.search(module.name, query)) ||
      (module.description && this.search(module.description, query));
  }

  /**
   * Search on a Page or Template.
   * @class Page
   * @class Template
   *
   * @param item
   * @param query
   */
  public static onPageOrTemplate(item: Page | Template, query: string): boolean {
    return (item.name && this.search(item.name, query));
  }


  /**
   * ------------------------
   * Helper functions
   * ------------------------
   */

  private static parse(str: string): string[] {
    const res: string[] = str.toFlat().split(this.IGNORED);

    const flat = str.toFlat();
    if (!res.includes(flat))
      res.push(flat);

    let glued = flat.replace(this.IGNORED, '');
    if (!res.includes(glued))
      res.push(glued);

    return res;
  }

  protected static search(target: string, query: string): boolean {
    return !!this.parse(target).find(a => a.includes(query.toFlat().replace(this.IGNORED, '')));
  }

}


/**
 * This class has utility functions to filter through
 * specific types of items.
 *
 * It is meant to be used only by Reduce and tests.
 * @class Reduce
 */
export class Filter {

  /**
   * Filter through a Course.
   * @class Course
   *
   * @param course
   * @param filter
   */
  public static onCourse(course: Course, filter: string): boolean {
    return (filter.match(/^active$/gi) && course.isActive) ||
      (filter.match(/^inactive$/gi) && !course.isActive) ||
      (filter.match(/^visible$/gi) && course.isVisible) ||
      (filter.match(/^invisible$/gi) && !course.isVisible)
  }

  /**
   * Filter through a User.
   * @class User
   *
   * @param user
   * @param filter
   */
  public static onUser(user: User, filter: string): boolean {
    return (filter.match(/^admin$/gi) && user.isAdmin) ||
      (filter.match(/^nonadmin$/gi) && !user.isAdmin) ||
      (filter.match(/^active$/gi) && user.isActive) ||
      (filter.match(/^inactive$/gi) && !user.isActive) ||
      (user.roles && !!user.roles.find(role => filter.match(new RegExp('^' + role.name + '$', 'gi'))));
  }

}
