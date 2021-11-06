import {View} from "../view";

export let viewsAdded: Map<number, View>;    // Holds views that have already been added to the tree
export let viewTree: any[];                  // The view tree being built

/**
 * Builds a view tree to be sent to database by merging all aspects
 * according to view ids and viewIds.
 *
 * @param aspects
 */
export function buildViewTree(aspects: View[]): any[] {
  viewsAdded = new Map<number, View>();
  viewTree = [];

  // Go through each aspect and add to view tree
  for (const aspect of aspects) {
    aspect.buildViewTree();
  }
  return viewTree;
}
