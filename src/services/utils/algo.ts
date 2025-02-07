/**
 * Search for elem in a sorted array of objects
 * If the object is not found, return the index where it should be inserted
 *
 * @param arr Array of objects to search
 * @param elem Element to search for
 * @param key Key to use for comparison
 */
export function binarySearch<T, K extends keyof T>(arr: T[], elem: T | T[K], key?: K): number {
  if (arr.length === 0) return 0;

  const desc = key ? arr[0][key] > arr[arr.length - 1][key] : arr[0] > arr[arr.length - 1];

  let minIndex = 0;
  let maxIndex = arr.length - 1;
  let currentIndex: number;
  let currentElement: T | T[K];

  while (minIndex <= maxIndex) {
    currentIndex = ((minIndex + maxIndex) / 2) | 0;
    currentElement = key ? arr[currentIndex][key] : arr[currentIndex];

    const e1 = desc ? elem : currentElement;
    const e2 = desc ? currentElement : elem;

    if (e1 < e2) {
      minIndex = currentIndex + 1;
    } else if (e1 > e2) {
      maxIndex = currentIndex - 1;
    } else {
      return currentIndex;
    }
  }

  return minIndex;
}

/**
 * Round a number to N decimal places
 * @param num Number to round
 * @param places Number of decimal places
 * @param floor If true, round down instead of to nearest
 */
export function round(num: number, places: number, floor = false) {
  const pow = Math.pow(10, places);
  const int = num * pow;
  return (floor ? Math.floor : Math.round)(int) / pow;
}

/**
 * Round to nearest 0.5. Useful for pixels.
 * @param num Number to round
 */
export function roundHalf(num: number) {
  return Math.round(num * 2) / 2;
}

/** Choose a random element from an array */
export function randomChoice<T>(arr: T[]): T {
  return arr[Math.floor(Math.random() * arr.length)];
}

/**
 * Choose a random sub array from an array
 * https://stackoverflow.com/a/11935263/4745239
 */
export function randomSubarray<T>(arr: T[], size: number): T[] {
  if (arr.length <= size) return arr;
  var shuffled = arr.slice(0),
    i = arr.length,
    min = i - size,
    temp,
    index;
  while (i-- > min) {
    index = Math.floor((i + 1) * Math.random());
    temp = shuffled[index];
    shuffled[index] = shuffled[i];
    shuffled[i] = temp;
  }
  return shuffled.slice(min);
}

/** Set a timer that renews if existing */
export function setRenewingTimeout<T>(ctx: T, name: string, callback: (() => void) | null, delay: number): void {
  if (ctx[name]) window.clearTimeout(ctx[name]);
  ctx[name] = window.setTimeout(() => {
    ctx[name] = 0;
    callback?.();
  }, delay);
}

/** Checks if a object is numeric */
export function isNumber<T>(num: T): boolean {
  const cast = Number(num);
  return !isNaN(cast) && isFinite(cast);
}

/** Check if a property is truthy */
export function truthy<T, K extends keyof T>(obj: T, prop: K): obj is T & { [P in K]-?: T[K] } {
  return !!obj[prop];
}
