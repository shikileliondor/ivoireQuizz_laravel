import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:go_router/go_router.dart';

import '../theme/app_theme.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/splash',
    routes: <RouteBase>[
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/onboarding',
        builder: (context, state) => const OnboardingScreen(),
      ),
      GoRoute(
        path: '/auth',
        builder: (context, state) => const AuthScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) => MainScreen(child: child),
        routes: <RouteBase>[
          GoRoute(
            path: '/home',
            builder: (context, state) => const HomeScreen(),
          ),
          GoRoute(
            path: '/leaderboard',
            builder: (context, state) => const LeaderboardScreen(),
          ),
          GoRoute(
            path: '/friends',
            builder: (context, state) => const FriendsScreen(),
          ),
          GoRoute(
            path: '/profile',
            builder: (context, state) => const ProfileScreen(),
          ),
        ],
      ),
      GoRoute(
        path: '/quiz/:categoryId/:mode',
        builder: (context, state) {
          final categoryIdParam = state.pathParameters['categoryId'];
          final categoryId = int.tryParse(categoryIdParam ?? '');
          final mode = state.pathParameters['mode'] ?? '';
          return QuizScreen(categoryId: categoryId, mode: mode);
        },
      ),
      GoRoute(
        path: '/result',
        builder: (context, state) {
          final extra = (state.extra as Map<String, dynamic>?) ??
              <String, dynamic>{};
          return ResultScreen(result: extra);
        },
      ),
    ],
  );
});

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  final FlutterSecureStorage _storage = const FlutterSecureStorage();

  @override
  void initState() {
    super.initState();
    _redirect();
  }

  Future<void> _redirect() async {
    await Future<void>.delayed(const Duration(seconds: 2));

    if (!mounted) {
      return;
    }

    final token = await _storage.read(key: 'auth_token');
    final onboardingDone = await _storage.read(key: 'onboarding_done');

    if (!mounted) {
      return;
    }

    if (token != null && token.isNotEmpty) {
      context.go('/home');
      return;
    }

    if (onboardingDone == null) {
      context.go('/onboarding');
      return;
    }

    context.go('/auth');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.orange,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            children: <Widget>[
              const Spacer(),
              Text(
                'IvoireQuiz',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppColors.white,
                  fontSize: 42,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                'Connais-tu vraiment \nla Côte d\'Ivoire ?',
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: AppColors.white,
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const Spacer(),
              const CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(AppColors.white),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}

class MainScreen extends StatelessWidget {
  const MainScreen({required this.child, super.key});

  final Widget child;

  int _locationToIndex(String location) {
    if (location.startsWith('/leaderboard')) {
      return 1;
    }
    if (location.startsWith('/friends')) {
      return 2;
    }
    if (location.startsWith('/profile')) {
      return 3;
    }
    return 0;
  }

  @override
  Widget build(BuildContext context) {
    final currentIndex = _locationToIndex(GoRouterState.of(context).uri.path);

    return Scaffold(
      body: child,
      bottomNavigationBar: NavigationBar(
        selectedIndex: currentIndex,
        onDestinationSelected: (index) {
          switch (index) {
            case 0:
              context.go('/home');
            case 1:
              context.go('/leaderboard');
            case 2:
              context.go('/friends');
            case 3:
              context.go('/profile');
          }
        },
        destinations: const <NavigationDestination>[
          NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Home'),
          NavigationDestination(
            icon: Icon(Icons.emoji_events_outlined),
            label: 'Classement',
          ),
          NavigationDestination(
            icon: Icon(Icons.group_outlined),
            label: 'Amis',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}

class OnboardingScreen extends StatelessWidget {
  const OnboardingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _PlaceholderScreen(title: 'Onboarding');
  }
}

class AuthScreen extends StatelessWidget {
  const AuthScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _PlaceholderScreen(title: 'Authentification');
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _PlaceholderScreen(title: 'Accueil');
  }
}

class LeaderboardScreen extends StatelessWidget {
  const LeaderboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _PlaceholderScreen(title: 'Classement');
  }
}

class FriendsScreen extends StatelessWidget {
  const FriendsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _PlaceholderScreen(title: 'Amis');
  }
}

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return const _PlaceholderScreen(title: 'Profil');
  }
}

class QuizScreen extends StatelessWidget {
  const QuizScreen({required this.mode, this.categoryId, super.key});

  final int? categoryId;
  final String mode;

  @override
  Widget build(BuildContext context) {
    return _PlaceholderScreen(
      title: 'Quiz - mode: $mode, catégorie: ${categoryId ?? 'all'}',
    );
  }
}

class ResultScreen extends StatelessWidget {
  const ResultScreen({required this.result, super.key});

  final Map<String, dynamic> result;

  @override
  Widget build(BuildContext context) {
    return _PlaceholderScreen(title: 'Résultat: $result');
  }
}

class _PlaceholderScreen extends StatelessWidget {
  const _PlaceholderScreen({required this.title});

  final String title;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: Center(
        child: Text(
          title,
          style: Theme.of(context).textTheme.titleLarge,
          textAlign: TextAlign.center,
        ),
      ),
    );
  }
}
