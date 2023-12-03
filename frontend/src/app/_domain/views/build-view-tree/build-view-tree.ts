import { Aspect } from "../aspects/aspect";
import { View, ViewDatabase } from "../view";

export let viewTree: any[];                           // The view tree being built
export let viewsAdded: Map<number, ViewDatabase>;     // Holds building-blocks that have already been added to the tree
export let viewsDeleted: number[] = [];               // viewIds of views that were completely deleted -> delete from database
export let selectedAspect: Aspect;                    // Selected aspect for previewing and editing
let fakeId: number = -1;                              // Fake, negative ids, for new views, to be generated in backend

export function getFakeId() : number {
  const id = fakeId;
  fakeId -= 1;
  console.log(id);
  return id;
}

export function setSelectedAspect(aspect: Aspect) {
  selectedAspect = aspect;
}

/**
 * Builds a view tree to be sent to database by merging all aspects
 * according to view ids and viewIds.
 *
 * @param viewsOfAspects
 */
export function buildViewTree(viewsOfAspects: View[]): ViewDatabase[] {
  viewsAdded = new Map<number, ViewDatabase>();
  viewTree = [];

  // Go through each aspect and add to view tree
  for (const view of viewsOfAspects) {
    view.buildViewTree();
  }
  return viewTree;
}
